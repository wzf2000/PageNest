<?php
/** Synthetic gettext uses the shipped MO, not a duplicate translation dictionary. */
$fixture_locale = 'zh_CN';
function fixture_catalog()
{
    static $catalog;
    if ($catalog !== null) {
        return $catalog;
    }
    $bytes = file_get_contents(__DIR__ . '/../languages/zh_CN.mo');
    $header = unpack(
        'Vmagic/Vrevision/Vcount/Voriginal/Vtranslated/Vhash_count/Vhash_offset',
        $bytes,
    );
    if (
        $header['magic'] !== 0x950412de ||
        $header['hash_offset'] - $header['translated'] !== $header['count'] * 8
    ) {
        throw new RuntimeException('Invalid MO');
    }
    $catalog = [];
    for ($i = 0; $i < $header['count']; $i++) {
        $original = unpack('Vlength/Voffset', substr($bytes, $header['original'] + 8 * $i, 8));
        $translated = unpack('Vlength/Voffset', substr($bytes, $header['translated'] + 8 * $i, 8));
        $key = explode("\0", substr($bytes, $original['offset'], $original['length']))[0];
        $catalog[$key] = substr($bytes, $translated['offset'], $translated['length']);
    }
    return $catalog;
}
function __($text, $domain = 'default')
{
    global $fixture_locale;
    return $domain === 'pagenest' && $fixture_locale === 'zh_CN'
        ? fixture_catalog()[$text] ?? $text
        : $text;
}
function _n($single, $plural, $number, $domain = 'default')
{
    global $fixture_locale;
    return $fixture_locale === 'zh_CN' ? __($single, $domain) : ($number === 1 ? $single : $plural);
}
function esc_html__($text, $domain = 'default')
{
    return esc_html(__($text, $domain));
}
function esc_attr__($text, $domain = 'default')
{
    return esc_attr(__($text, $domain));
}
