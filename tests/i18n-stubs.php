<?php
/** Synthetic gettext uses the shipped MO, not a duplicate translation dictionary. */
$fixture_locale = 'zh_CN';
function fixture_catalog()
{
    global $fixture_locale;
    static $catalogs = [];
    if (isset($catalogs[$fixture_locale])) {
        return $catalogs[$fixture_locale];
    }
    $path = __DIR__ . '/../languages/' . $fixture_locale . '.mo';
    if (!preg_match('/^[a-z]{2,3}(?:_[A-Za-z0-9]+)*$/D', $fixture_locale) || !is_file($path)) {
        return [];
    }
    $bytes = file_get_contents($path);
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
    $catalogs[$fixture_locale] = $catalog;
    return $catalog;
}
function __($text, $domain = 'default')
{
    global $fixture_locale;
    return $domain === 'pagenest' && in_array($fixture_locale, ['zh_CN', 'zh_TW'], true)
        ? fixture_catalog()[$text] ?? $text
        : $text;
}
function _n($single, $plural, $number, $domain = 'default')
{
    global $fixture_locale;
    return in_array($fixture_locale, ['zh_CN', 'zh_TW'], true)
        ? __($single, $domain)
        : ($number === 1
            ? $single
            : $plural);
}
function esc_html__($text, $domain = 'default')
{
    return esc_html(__($text, $domain));
}
function esc_attr__($text, $domain = 'default')
{
    return esc_attr(__($text, $domain));
}
