<?php

namespace VirtualisAkademia\Content;

/**
 * Turns the lessons' big "Egyedi HTML" blocks into native Gutenberg blocks,
 * so text and pictures can be edited visually:
 *
 *   p, h1-h6, ul/ol (nested too), blockquote, hr, table, figure/img, picture
 *   rows, the "Összegzés" box, "Háttérinformáció" notes and the purely
 *   textual callouts (Tanulási cél, Rövid feladat … without any task).
 *
 * Everything else -- the interactive tasks, games, flow charts, timelines
 * and their <script>s -- stays exactly as authored, byte for byte, in
 * Custom HTML blocks. Work is done on exact source slices (the HTML is never
 * re-serialised), so nothing in those blocks can change.
 *
 * The lesson wrapper (`.va-lesson`) is no longer stored in the content: it
 * is added around the output by Integration\LearnDash\LessonContentWrapper.
 *
 * Idempotent: only `core/html` blocks are looked at, native ones are left.
 */
class LessonBlockConverter {

	/** Wrapper classes of the old lesson markup (transparent to the converter). */
	private const WRAPPER = '/(^|\s)(va-lesson|democracy-content-wrapper)(\s|$)/';

	/** Markers of interactive markup: such a callout is never converted. */
	private const INTERACTIVE = '/data-va|onclick|onchange|oninput|<button|<select|<input|<textarea|<script|<style|draggable|contenteditable|\sid=/i';

	private const BLOCKLEVEL = '/<(p|div|table|h[1-6]|blockquote|figure|ul|ol|hr|section|pre|details|form)\b/i';

	private const VOID = [ 'img', 'br', 'hr', 'input', 'meta', 'link', 'source', 'wbr', 'col', 'area', 'embed', 'param', 'track' ];

	/** @var array<string, int> */
	private array $stats = [];

	/**
	 * @return array{content: string, stats: array<string, int>}
	 */
	public function convert( string $content ): array {
		$this->stats = [];
		$out         = [];

		foreach ( parse_blocks( $content ) as $block ) {
			if ( 'core/html' === $block['blockName'] ) {
				foreach ( $this->convert_html( (string) $block['innerHTML'] ) as $new ) {
					$out[] = $new;
				}
				continue;
			}

			if ( null === $block['blockName'] && '' === trim( (string) $block['innerHTML'] ) ) {
				continue; // inter-block whitespace
			}

			$out[] = $block;
		}

		return [
			'content' => implode( "\n\n", array_map( 'serialize_block', $out ) ),
			'stats'   => $this->stats,
		];
	}

	/** Native vs. Custom HTML block counts of a post's content, for the admin overview. */
	public static function count_blocks( string $content ): array {
		$count = [ 'native' => 0, 'html' => 0 ];

		$walk = static function ( array $blocks ) use ( &$walk, &$count ): void {
			foreach ( $blocks as $block ) {
				if ( null === $block['blockName'] ) {
					continue;
				}
				'core/html' === $block['blockName'] ? ++$count['html'] : ++$count['native'];
				$walk( $block['innerBlocks'] );
			}
		};

		$walk( parse_blocks( $content ) );

		return $count;
	}

	// --- Pass over one Custom HTML block -------------------------------------------

	/** @return array<int, array> blocks */
	private function convert_html( string $html ): array {
		$nodes = $this->nodes( $html );

		if ( null === $nodes ) {
			return [ $this->html_block( $html ) ]; // not parseable: keep as is
		}

		$nodes  = $this->flatten_wrappers( $html, $nodes );
		$blocks = [];
		$run    = ''; // consecutive non-native source, kept together

		$flush = function () use ( &$run, &$blocks ): void {
			if ( '' !== trim( $run ) ) {
				$blocks[] = $this->html_block( trim( $run ) );
				$this->bump( 'html' );
			}
			$run = '';
		};

		$pending_comments = '';

		foreach ( $nodes as $node ) {
			if ( 'comment' === $node['type'] ) {
				$pending_comments .= $node['src']->slice( $node ) . "\n";
				continue;
			}

			$native = 'element' === $node['type'] ? $this->native( $node ) : null;

			if ( null === $native ) {
				$run .= ( '' === $run ? $pending_comments : '' ) . $node['src']->slice( $node ) . "\n";
			} else {
				$flush();
				array_push( $blocks, ...$native );
			}

			$pending_comments = '';
		}

		$flush();

		return $blocks;
	}

	/**
	 * Wrapper divs (`.va-lesson` / `.democracy-content-wrapper`, no other
	 * attributes) are transparent: their children are the nodes.
	 *
	 * @return array<int, array>
	 */
	private function flatten_wrappers( string $html, array $nodes ): array {
		$flat = [];

		foreach ( $nodes as $node ) {
			if ( 'element' === $node['type'] && 'div' === $node['tag'] && $this->is_wrapper( $node['attrs'] ) ) {
				$inner = substr( $html, $node['inner_start'], $node['inner_end'] - $node['inner_start'] );
				$kids  = $this->nodes( $inner );

				if ( null !== $kids ) {
					foreach ( $this->flatten_wrappers( $inner, $kids ) as $kid ) {
						$flat[] = $kid;
					}
					continue;
				}
			}

			$flat[] = $node;
		}

		return $flat;
	}

	private function is_wrapper( string $attrs ): bool {
		$a = $this->attrs( $attrs );

		return 1 === count( $a ) && isset( $a['class'] ) && preg_match( self::WRAPPER, $a['class'] );
	}

	// --- Node -> native blocks (or null) ---------------------------------------------

	/** @return array<int, array>|null */
	private function native( array $node ): ?array {
		$tag   = $node['tag'];
		$attrs = $this->attrs( $node['attrs'] );
		$inner = $node['inner'];

		switch ( true ) {
			case 'p' === $tag:
				$class = $this->plain_class( $attrs, [ 'style' => 'va-note' ] );
				return $this->inline_ok( $inner ) && false !== $class ? [ $this->paragraph( $inner, $class ) ] : null;

			case 1 === preg_match( '/^h([1-6])$/', $tag, $m ):
				$class = $this->plain_class( $attrs );
				return $this->inline_ok( $inner ) && false !== $class ? [ $this->heading( (int) $m[1], $inner, $class ) ] : null;

			case 'ul' === $tag || 'ol' === $tag:
				$class = $this->plain_class( $attrs );
				$list  = false !== $class ? $this->list_block( $node, $class ) : null;
				return $list ? [ $list ] : null;

			case 'blockquote' === $tag:
				return $attrs ? null : $this->quote( $inner );

			case 'hr' === $tag:
				return $attrs ? null : [ $this->leaf( 'core/separator', [], '<hr class="wp-block-separator has-alpha-channel-opacity"/>' ) ];

			case 'table' === $tag:
				$class = $this->plain_class( $attrs );
				$t     = false !== $class ? $this->table( $node['src']->slice( $node ), $class ) : null;
				return $t ? [ $t ] : null;

			case 'figure' === $tag:
				$img = $this->image( $node );
				return $img ? [ $img ] : null;

			case 'div' === $tag:
				return $this->native_div( $node, $attrs );
		}

		return null;
	}

	/** @return array<int, array>|null */
	private function native_div( array $node, array $attrs ): ?array {
		$class   = $attrs['class'] ?? '';
		$classes = preg_split( '/\s+/', trim( $class ) ) ?: [];
		$inner   = $node['inner'];
		$outer   = $node['src']->slice( $node );

		// Only a class attribute (and the figure row's --va-cols style) is understood.
		$extra = array_diff( array_keys( $attrs ), [ 'class', 'style' ] );

		if ( $extra ) {
			return null;
		}

		if ( in_array( 'va-table-scroll', $classes, true ) && ! isset( $attrs['style'] ) ) {
			$kids = $this->nodes( $inner );
			$kids = null === $kids ? null : array_values( array_filter( $kids, static fn( $k ) => 'comment' !== $k['type'] ) );

			if ( $kids && 1 === count( $kids ) && 'table' === ( $kids[0]['tag'] ?? '' ) ) {
				$tattrs = $this->attrs( $kids[0]['attrs'] );
				$t      = $this->table( $kids[0]['src']->slice( $kids[0] ), (string) ( $tattrs['class'] ?? '' ) );
				return $t ? [ $t ] : null;
			}

			return null;
		}

		if ( in_array( 'va-figure-row', $classes, true ) ) {
			return $this->gallery( $node );
		}

		if ( preg_match( self::INTERACTIVE, $outer ) || isset( $attrs['style'] ) ) {
			return null; // interactive / styled: stays HTML
		}

		if ( ( in_array( 'info-text', $classes, true ) || in_array( 'template-box', $classes, true ) ) && ! preg_match( self::BLOCKLEVEL, $inner ) ) {
			return $this->inline_ok( $inner ) ? [ $this->paragraph( $inner, $class ) ] : null;
		}

		if ( in_array( 'va-summary', $classes, true ) || in_array( 'callout', $classes, true ) ) {
			$children = $this->children_blocks( $inner );

			return $children ? [ $this->group( $class, $children ) ] : null;
		}

		return null;
	}

	/**
	 * A container's content as native blocks -- or null when anything in it
	 * isn't convertible (then the whole container stays HTML).
	 *
	 * @return array<int, array>|null
	 */
	private function children_blocks( string $inner ): ?array {
		$kids = $this->nodes( $inner );

		if ( null === $kids ) {
			return null;
		}

		$out = [];

		foreach ( $kids as $kid ) {
			if ( 'comment' === $kid['type'] ) {
				continue;
			}

			if ( 'element' !== $kid['type'] ) {
				return null; // loose text
			}

			$attrs = $this->attrs( $kid['attrs'] );

			// The label of a callout: <div class="callout-label">…</div>.
			if ( 'div' === $kid['tag'] && isset( $attrs['class'] ) && 1 === count( $attrs ) && 'callout-label' === trim( $attrs['class'] ) ) {
				if ( ! $this->inline_ok( $kid['inner'] ) ) {
					return null;
				}
				$out[] = $this->paragraph( $kid['inner'], 'callout-label' );
				continue;
			}

			$native = $this->native( $kid );

			if ( null === $native ) {
				return null;
			}

			array_push( $out, ...$native );
		}

		return $out ?: null;
	}

	// --- Block builders -----------------------------------------------------------------

	private function leaf( string $name, array $attrs, string $html ): array {
		$this->bump( $name );

		return [ 'blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => [], 'innerHTML' => $html, 'innerContent' => [ $html ] ];
	}

	private function container( string $name, array $attrs, string $open, array $children, string $close ): array {
		$this->bump( $name );
		$content = [ $open ];

		foreach ( $children as $i => $child ) {
			if ( $i > 0 ) {
				$content[] = "\n\n";
			}
			$content[] = null;
		}

		$content[] = $close;

		return [ 'blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => $children, 'innerHTML' => $open . $close, 'innerContent' => $content ];
	}

	private function html_block( string $html ): array {
		return [ 'blockName' => 'core/html', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => $html, 'innerContent' => [ $html ] ];
	}

	private function cls( string $base, string $extra ): string {
		return trim( $base . ( '' !== $extra ? ' ' . $extra : '' ) );
	}

	private function paragraph( string $inner, string $class = '' ): array {
		$attrs = '' !== $class ? [ 'className' => $class ] : [];
		$open  = '' !== $class ? '<p class="' . esc_attr( $class ) . '">' : '<p>';

		return $this->leaf( 'core/paragraph', $attrs, $open . trim( $inner ) . '</p>' );
	}

	private function heading( int $level, string $inner, string $class = '' ): array {
		$attrs = ( 2 !== $level ? [ 'level' => $level ] : [] ) + ( '' !== $class ? [ 'className' => $class ] : [] );

		return $this->leaf( 'core/heading', $attrs, sprintf( '<h%1$d class="%2$s">%3$s</h%1$d>', $level, esc_attr( $this->cls( 'wp-block-heading', $class ) ), trim( $inner ) ) );
	}

	private function group( string $class, array $children ): array {
		return $this->container( 'core/group', [ 'className' => $class ], '<div class="' . esc_attr( $this->cls( 'wp-block-group', $class ) ) . '">', $children, '</div>' );
	}

	private function list_block( array $node, string $class ): ?array {
		$ordered = 'ol' === $node['tag'];
		$kids    = $this->nodes( $node['inner'] );

		if ( null === $kids ) {
			return null;
		}

		$items = [];

		foreach ( $kids as $kid ) {
			if ( 'comment' === $kid['type'] ) {
				continue;
			}

			if ( 'element' !== $kid['type'] || 'li' !== $kid['tag'] || '' !== trim( $kid['attrs'] ) ) {
				return null;
			}

			$li = $this->nodes( $kid['inner'] );

			if ( null === $li ) {
				return null;
			}

			$content = $kid['inner'];
			$nested  = [];

			foreach ( $li as $n ) {
				if ( 'element' === $n['type'] && in_array( $n['tag'], [ 'ul', 'ol' ], true ) ) {
					if ( ! $nested ) {
						$content = substr( $kid['inner'], 0, $n['start'] );
					}
					$sub = $this->native( $n );

					if ( null === $sub ) {
						return null;
					}
					array_push( $nested, ...$sub );
				} elseif ( $nested && ( 'element' === $n['type'] || '' !== trim( $n['src']->slice( $n ) ) ) ) {
					return null; // content after a nested list
				}
			}

			if ( ! $this->inline_ok( $content ) ) {
				return null;
			}

			$items[] = $this->container( 'core/list-item', [], '<li>' . trim( $content ), $nested, '</li>' );
		}

		if ( ! $items ) {
			return null;
		}

		$tag   = $ordered ? 'ol' : 'ul';
		$attrs = ( $ordered ? [ 'ordered' => true ] : [] ) + ( '' !== $class ? [ 'className' => $class ] : [] );

		return $this->container( 'core/list', $attrs, '<' . $tag . ' class="' . esc_attr( $this->cls( 'wp-block-list', $class ) ) . '">', $items, '</' . $tag . '>' );
	}

	/** @return array<int, array>|null */
	private function quote( string $inner ): ?array {
		if ( preg_match( '/<(cite|footer)\b/i', $inner ) ) {
			return null;
		}

		$paragraphs = [];

		if ( preg_match( '/<p\b/i', $inner ) ) {
			$kids = $this->nodes( $inner );

			if ( null === $kids ) {
				return null;
			}

			foreach ( $kids as $kid ) {
				if ( 'comment' === $kid['type'] ) {
					continue;
				}

				if ( 'element' !== $kid['type'] || 'p' !== $kid['tag'] || '' !== trim( $kid['attrs'] ) || ! $this->inline_ok( $kid['inner'] ) ) {
					return null;
				}

				$paragraphs[] = $this->paragraph( $kid['inner'] );
			}
		} elseif ( $this->inline_ok( $inner ) && '' !== trim( $inner ) ) {
			$paragraphs[] = $this->paragraph( $inner );
		}

		return $paragraphs ? [ $this->container( 'core/quote', [], '<blockquote class="wp-block-quote">', $paragraphs, '</blockquote>' ) ] : null;
	}

	private function table( string $outer, string $class ): ?array {
		if ( ! preg_match( '~^<table\b[^>]*>(.*)</table>\s*$~is', $outer, $m ) ) {
			return null;
		}

		$body = $m[1];

		// Plain cells only (no classes / styles), no nested tables.
		if ( preg_match( '~<t[dh]\s[^>]*\b(class|style|id|data-\w+|on\w+)\s*=~i', $body ) || preg_match( '~<table\b~i', $body ) || preg_match( '~<(script|input|button|select)\b~i', $body ) ) {
			return null;
		}

		$body = preg_replace( '~>\s+<~', '><', trim( $body ) );
		$body = preg_replace( '~(<t[dh][^>]*>)\s+~', '$1', $body );
		$body = preg_replace( '~\s+(</t[dh]>)~', '$1', $body );

		if ( ! preg_match( '~^(<(thead|tbody|tfoot)>.*?</\2>)+$~s', $body ) ) {
			$body = '<tbody>' . $body . '</tbody>'; // bare rows
		}

		$attrs = [ 'hasFixedLayout' => false ] + ( '' !== $class ? [ 'className' => $class ] : [] );

		return $this->leaf( 'core/table', $attrs, '<figure class="' . esc_attr( $this->cls( 'wp-block-table', $class ) ) . '"><table>' . $body . '</table></figure>' );
	}

	private function image( array $node ): ?array {
		$attrs = $this->attrs( $node['attrs'] );
		$class = trim( (string) ( $attrs['class'] ?? '' ) );

		if ( array_diff( array_keys( $attrs ), [ 'class' ] ) || ! str_contains( $class, 'va-figure' ) || preg_match( '/<figcaption/i', $node['inner'] ) ) {
			return null;
		}

		return $this->image_from( $node['inner'], $class );
	}

	private function image_from( string $inner, string $class ): ?array {
		if ( ! preg_match( '~^\s*<img\b([^>]*)>\s*$~is', $inner, $m ) ) {
			return null;
		}

		$img = $this->attrs( $m[1] );
		$src = (string) ( $img['data-va-full'] ?? $img['src'] ?? '' );

		if ( '' === $src ) {
			return null;
		}

		$id = (int) attachment_url_to_postid( $src );

		if ( ! $id && isset( $img['src'] ) ) {
			// resized URL ("…-1024x576.webp") -> the original file's attachment
			$id = (int) attachment_url_to_postid( preg_replace( '~-\d+x\d+(\.\w+)$~', '$1', $img['src'] ) );
		}

		$alt   = (string) ( $img['alt'] ?? '' );
		$url   = $id ? ( wp_get_attachment_image_url( $id, 'large' ) ?: $src ) : $src;
		$attrs = [ 'sizeSlug' => 'large', 'linkDestination' => 'none' ];

		if ( $id ) {
			$attrs = [ 'id' => $id ] + $attrs;
		}

		if ( '' !== $class ) {
			$attrs['className'] = $class;
		}

		$figure = sprintf(
			'<figure class="%s"><img src="%s" alt="%s"%s/></figure>',
			esc_attr( $this->cls( 'wp-block-image size-large', $class ) ),
			esc_url( $url ),
			esc_attr( $alt ),
			$id ? ' class="wp-image-' . $id . '"' : ''
		);

		return $this->leaf( 'core/image', $attrs, $figure );
	}

	/** @return array<int, array>|null */
	private function gallery( array $node ): ?array {
		$kids   = $this->nodes( $node['inner'] );
		$images = [];

		if ( null === $kids ) {
			return null;
		}

		foreach ( $kids as $kid ) {
			if ( 'comment' === $kid['type'] ) {
				continue;
			}

			if ( 'element' !== $kid['type'] || 'figure' !== $kid['tag'] || preg_match( '/<figcaption/i', $kid['inner'] ) ) {
				return null;
			}

			$img = $this->image_from( $kid['inner'], '' );

			if ( ! $img ) {
				return null;
			}

			$images[] = $img;
		}

		if ( count( $images ) < 2 ) {
			return null;
		}

		$cols = min( 3, count( $images ) );

		return [ $this->container(
			'core/gallery',
			[ 'columns' => $cols, 'linkTo' => 'none', 'className' => 'va-figure-row' ],
			'<figure class="wp-block-gallery has-nested-images columns-' . $cols . ' is-cropped va-figure-row">',
			$images,
			'</figure>'
		) ];
	}

	// --- Attribute helpers ------------------------------------------------------------------

	/**
	 * Class to carry over for a plain element: '' (none), a class string, or
	 * false when it has attributes that can't be expressed natively.
	 *
	 * @param array<string,string> $map attribute => replacement class (e.g. the one known inline style)
	 * @return string|false
	 */
	private function plain_class( array $attrs, array $map = [] ) {
		$class = '';

		foreach ( $attrs as $name => $value ) {
			if ( 'class' === $name ) {
				$class = trim( $value );
			} elseif ( 'style' === $name && isset( $map['style'] ) && str_contains( $value, 'font-style: italic' ) && str_contains( $value, 'lc-text-muted' ) ) {
				$class = $this->cls( $class, $map['style'] );
			} else {
				return false;
			}
		}

		return $class;
	}

	/** Inline content only: no block-level tags, no script/style. */
	private function inline_ok( string $html ): bool {
		return ! preg_match( self::BLOCKLEVEL, $html ) && ! preg_match( '/<(script|style|input|button|select|textarea)\b/i', $html );
	}

	/** @return array<string,string> */
	private function attrs( string $raw ): array {
		$out = [];

		if ( preg_match_all( '/([\w:-]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?/', $raw, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $a ) {
				$value = $a[2] ?? '';
				$value = '' !== $value ? $value : ( $a[3] ?? '' );
				$value = '' !== $value ? $value : ( $a[4] ?? '' );
				$out[ strtolower( $a[1] ) ] = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			}
		}

		return $out;
	}

	private function bump( string $key ): void {
		$this->stats[ $key ] = ( $this->stats[ $key ] ?? 0 ) + 1;
	}

	// --- Tokenizer ----------------------------------------------------------------------------

	/**
	 * Top-level nodes of an HTML string, as exact source ranges. Null when
	 * the markup is unbalanced (then the caller keeps the source untouched).
	 *
	 * @return array<int, array>|null
	 */
	private function nodes( string $html ): ?array {
		// Mask comment / script / style bodies (same length) so their text can't be mistaken for tags.
		$masked = preg_replace_callback(
			'~<!--.*?-->|(<(script|style)\b[^>]*>)(.*?)(</\2\s*>)~is',
			static function ( $m ) {
				if ( '<!--' === substr( $m[0], 0, 4 ) ) {
					return '<!--' . str_repeat( 'x', max( 0, strlen( $m[0] ) - 7 ) ) . '-->';
				}

				return $m[1] . str_repeat( 'x', strlen( $m[3] ) ) . $m[4];
			},
			$html
		);

		if ( null === $masked ) {
			return null;
		}

		$src   = new class( $html ) {
			public function __construct( private string $html ) {}

			public function slice( array $node ): string {
				return substr( $this->html, $node['start'], $node['end'] - $node['start'] );
			}
		};
		$nodes = [];
		$stack = [];
		$last  = 0;
		$open  = null;

		preg_match_all( '~<!--.*?-->|<(/?)([a-zA-Z][\w:-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>~s', $masked, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

		foreach ( $tags as $t ) {
			$full  = $t[0][0];
			$start = $t[0][1];
			$end   = $start + strlen( $full );

			if ( ! $stack && $start > $last && '' !== trim( substr( $html, $last, $start - $last ) ) ) {
				$nodes[] = [ 'type' => 'text', 'tag' => '', 'attrs' => '', 'start' => $last, 'end' => $start, 'src' => $src, 'inner' => '' ];
			}

			if ( '<!--' === substr( $full, 0, 4 ) ) {
				if ( ! $stack ) {
					$nodes[] = [ 'type' => 'comment', 'tag' => '', 'attrs' => '', 'start' => $start, 'end' => $end, 'src' => $src, 'inner' => '' ];
					$last    = $end;
				}
				continue;
			}

			$closing = '/' === $t[1][0];
			$name    = strtolower( $t[2][0] );
			$attrs   = trim( $t[3][0] );
			$self    = ! $closing && ( in_array( $name, self::VOID, true ) || '/' === substr( $attrs, -1 ) );

			if ( $self ) {
				if ( ! $stack ) {
					$nodes[] = [ 'type' => 'element', 'tag' => $name, 'attrs' => rtrim( $attrs, '/ ' ), 'start' => $start, 'end' => $end, 'src' => $src, 'inner' => '', 'inner_start' => $end, 'inner_end' => $end ];
					$last    = $end;
				}
				continue;
			}

			if ( $closing ) {
				if ( ! $stack || array_pop( $stack ) !== $name ) {
					return null;
				}

				if ( ! $stack && $open ) {
					$open['end']       = $end;
					$open['inner_end'] = $start;
					$open['inner']     = substr( $html, $open['inner_start'], $start - $open['inner_start'] );
					$nodes[]           = $open;
					$open              = null;
					$last              = $end;
				}
				continue;
			}

			if ( ! $stack ) {
				$open = [ 'type' => 'element', 'tag' => $name, 'attrs' => $attrs, 'start' => $start, 'end' => $end, 'src' => $src, 'inner' => '', 'inner_start' => $end, 'inner_end' => $end ];
			}

			$stack[] = $name;
		}

		if ( $stack ) {
			return null;
		}

		if ( '' !== trim( substr( $html, $last ) ) ) {
			$nodes[] = [ 'type' => 'text', 'tag' => '', 'attrs' => '', 'start' => $last, 'end' => strlen( $html ), 'src' => $src, 'inner' => '' ];
		}

		return $nodes;
	}
}
