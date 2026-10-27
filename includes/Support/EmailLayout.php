<?php

namespace VirtualisAkademia\Support;

/**
 * The branded e-mail card shared by every message the site sends (the
 * certificate e-mail and the Ultimate Member notifications): dark EU-blue
 * card, gold top rule, Montserrat heading, cyan pill button. Table layout +
 * inline styles, for mail clients.
 *
 * Values may contain Ultimate Member placeholders ("{first_name}",
 * "{password_reset_link}"): UM replaces them after the template has been
 * rendered, so a URL that is exactly one placeholder is emitted as-is
 * (esc_url() would strip the braces).
 */
class EmailLayout {

	private const FONT_BODY    = "Inter,'Segoe UI',Helvetica,Arial,sans-serif";
	private const FONT_DISPLAY = "Montserrat,'Segoe UI',Helvetica,Arial,sans-serif";
	private const FONT_MONO    = "'JetBrains Mono',Consolas,monospace";

	/**
	 * @param array{
	 *     eyebrow?: string,
	 *     heading: string,
	 *     paragraphs?: string[],
	 *     details?: array<string, string>,
	 *     cta?: array{url: string, label: string},
	 *     after?: string[],
	 *     note?: string,
	 * } $args Paragraphs / after are trusted HTML; everything else is text.
	 */
	public static function card( array $args ): string {
		$eyebrow = $args['eyebrow'] ?? 'Virtuális Akadémia';

		ob_start();
		?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#060e20;padding:24px 12px;">
	<tr><td align="center">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#0b1326;border:1px solid #2d3449;border-radius:12px;overflow:hidden;font-family:<?php echo self::FONT_BODY; ?>;">
			<tr><td style="padding:28px 32px 8px;border-top:4px solid #fecb00;">
				<p style="margin:0 0 6px;font-family:<?php echo self::FONT_MONO; ?>;font-size:11px;letter-spacing:2px;color:#fecb00;text-transform:uppercase;"><?php echo esc_html( $eyebrow ); ?></p>
				<h1 style="margin:0;font-family:<?php echo self::FONT_DISPLAY; ?>;font-size:24px;line-height:1.25;color:#dae2fd;"><?php echo esc_html( $args['heading'] ); ?></h1>
			</td></tr>
			<tr><td style="padding:12px 32px 4px;font-size:15px;line-height:1.6;color:#c4c5d5;">
				<?php foreach ( $args['paragraphs'] ?? [] as $paragraph ) : ?>
					<p style="margin:0 0 14px;"><?php echo $paragraph; // phpcs:ignore -- trusted template HTML. ?></p>
				<?php endforeach; ?>
			</td></tr>
			<?php if ( ! empty( $args['details'] ) ) : ?>
				<tr><td style="padding:0 32px 8px;">
					<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#060e20;border:1px solid #2d3449;border-radius:8px;">
						<?php foreach ( $args['details'] as $label => $value ) : ?>
							<tr>
								<td width="1%" style="padding:10px 14px;font-family:<?php echo self::FONT_MONO; ?>;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#9aa0b4;white-space:nowrap;vertical-align:top;"><?php echo esc_html( $label ); ?></td>
								<td style="padding:10px 14px;font-size:14px;color:#dae2fd;word-break:break-all;"><?php echo $value; // phpcs:ignore -- placeholder or trusted HTML. ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</td></tr>
			<?php endif; ?>
			<?php if ( ! empty( $args['cta'] ) ) : ?>
				<tr><td style="padding:14px 32px 24px;">
					<a href="<?php echo self::url( $args['cta']['url'] ); ?>" style="display:inline-block;padding:12px 26px;border-radius:999px;background:#00dbe9;color:#00363a;font-family:<?php echo self::FONT_DISPLAY; ?>;font-size:13px;font-weight:700;letter-spacing:0.5px;text-decoration:none;text-transform:uppercase;"><?php echo esc_html( $args['cta']['label'] ); ?></a>
				</td></tr>
			<?php endif; ?>
			<?php if ( ! empty( $args['after'] ) ) : ?>
				<tr><td style="padding:0 32px 20px;font-size:13px;line-height:1.6;color:#9aa0b4;">
					<?php foreach ( $args['after'] as $paragraph ) : ?>
						<p style="margin:0 0 10px;"><?php echo $paragraph; // phpcs:ignore -- trusted template HTML. ?></p>
					<?php endforeach; ?>
				</td></tr>
			<?php endif; ?>
			<tr><td style="padding:14px 32px;background:#060e20;border-top:1px solid #2d3449;font-family:<?php echo self::FONT_MONO; ?>;font-size:11px;line-height:1.6;color:#9aa0b4;">
				<?php echo $args['note'] ?? esc_html__( 'Ezt a levelet a rendszer automatikusan küldte, kérjük, ne válaszolj rá.', 'virtualis-akademia' ); // phpcs:ignore -- trusted template HTML. ?>
			</td></tr>
		</table>
	</td></tr>
</table>
		<?php
		return (string) ob_get_clean();
	}

	/** A full HTML document around card() -- for mail sent outside UM. */
	public static function document( array $args ): string {
		return '<!doctype html><html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
			. '<body style="margin:0;padding:0;background:#060e20;">' . self::card( $args ) . '</body></html>';
	}

	/** Inline link, e.g. "írj nekünk" → mailto:{admin_email}. */
	public static function link( string $url, string $label ): string {
		return '<a href="' . self::url( $url ) . '" style="color:#00dbe9;text-decoration:none;">' . esc_html( $label ) . '</a>';
	}

	private static function url( string $url ): string {
		if ( preg_match( '/^(mailto:)?\{[a-z_]+\}$/', $url ) ) {
			return $url;
		}

		return esc_url( $url );
	}
}
