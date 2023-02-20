<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Yiisoft\Aliases\Aliases;

Class CountryHelper
{

/**
 * Returns an array list of cldr => country, translated in the language $cldr.
 * If there is no translated country list, return english.
 *
 * @param string $cldr
 * @return mixed
 */    
public function get_country_list(string $cldr) : mixed
{
    $new_aliases = new Aliases(['@helpers' => __DIR__, '@country_list' => '@helpers/Country-list']);
    $file = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR. $cldr .DIRECTORY_SEPARATOR.'country.php';
    $default_english = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.'country.php';
    if (file_exists($file)) {
        /**
         * @psalm-suppress UnresolvableInclude
         */
        return (include $file);
    } else {
        /**
         * @psalm-suppress UnresolvableInclude
         */
        return (include $default_english);
    }
}

/**
 * Returns the countryname of a given $countrycode, translated in the language $cldr.
 *
 * @param string $cldr
 * @param string $countrycode
 * @return mixed
 */
public function get_country_name(string $cldr, string $countrycode)
{
    $countries = $this->get_country_list($cldr);
    return (isset($countries[$countrycode]) ? $countries[$countrycode] : $countrycode);
}
}