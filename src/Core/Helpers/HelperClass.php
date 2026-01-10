<?php
namespace Core\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HelperClass
{
    public static function sluggableCustomSlugMethod($string, $separator = '-')
    {
        $_transliteration = array(
            '/ä|æ|ǽ/' => 'ae',
            '/ö|œ/' => 'oe',
            '/ü/' => 'ue',
            '/Ä/' => 'Ae',
            '/Ü/' => 'Ue',
            '/Ö/' => 'Oe',
            '/À|Á|Â|Ã|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
            '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
            '/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
            '/ç|ć|ĉ|ċ|č/' => 'c',
            '/Ð|Ď|Đ/' => 'D',
            '/ð|ď|đ/' => 'd',
            '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
            '/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
            '/Ĝ|Ğ|Ġ|Ģ/' => 'G',
            '/ĝ|ğ|ġ|ģ/' => 'g',
            '/Ĥ|Ħ/' => 'H',
            '/ĥ|ħ/' => 'h',
            '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
            '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
            '/Ĵ/' => 'J',
            '/ĵ/' => 'j',
            '/Ķ/' => 'K',
            '/ķ/' => 'k',
            '/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
            '/ĺ|ļ|ľ|ŀ|ł/' => 'l',
            '/Ñ|Ń|Ņ|Ň/' => 'N',
            '/ñ|ń|ņ|ň|ŉ/' => 'n',
            '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
            '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
            '/Ŕ|Ŗ|Ř/' => 'R',
            '/ŕ|ŗ|ř/' => 'r',
            '/Ś|Ŝ|Ş|Ș|Š/' => 'S',
            '/ś|ŝ|ş|ș|š|ſ/' => 's',
            '/Ţ|Ț|Ť|Ŧ/' => 'T',
            '/ţ|ț|ť|ŧ/' => 't',
            '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
            '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
            '/Ý|Ÿ|Ŷ/' => 'Y',
            '/ý|ÿ|ŷ/' => 'y',
            '/Ŵ/' => 'W',
            '/ŵ/' => 'w',
            '/Ź|Ż|Ž/' => 'Z',
            '/ź|ż|ž/' => 'z',
            '/Æ|Ǽ/' => 'AE',
            '/ß/' => 'ss',
            '/Ĳ/' => 'IJ',
            '/ĳ/' => 'ij',
            '/Œ/' => 'OE',
            '/ƒ/' => 'f'
        );
        $quotedReplacement = preg_quote($separator, '/');
        $merge = array(
            '/[^\s\p{Zs}\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
            '/[\s\p{Zs}]+/mu' => $separator,
            sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
        );
        $map = $_transliteration + $merge;
        unset($_transliteration);
        return preg_replace(array_keys($map), array_values($map), $string);
    }

    /**
     * Manually check if user is authenticated via Sanctum token and get user ID
     * This method doesn't use Auth facade or middleware
     *
     * @param Request $request
     * @return int|null Returns user ID if authenticated, null otherwise
     */
    public static function getUserIdFromToken(Request $request): ?int
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $fullToken = substr($authHeader, 7); // Remove "Bearer " prefix

        // Sanctum tokens are in format: {id}|{token}
        // We need to extract only the token part (after the |)
        if (str_contains($fullToken, '|')) {
            $tokenParts = explode('|', $fullToken, 2);
            $token = $tokenParts[1]; // Get the token part after the |
        } else {
            $token = $fullToken; // Fallback if no | separator
        }

        $hashedToken = hash('sha256', $token);

        // Check if token exists in personal_access_tokens table
        $tokenRecord = DB::table('personal_access_tokens')
            ->where('token', $hashedToken)
            ->first();

        return $tokenRecord ? $tokenRecord->tokenable_id : null;
    }

    /**
     * Convert number to Persian words
     * 
     * @param float $number
     * @return string
     */
    public static function numberToPersianWords(float $number): string
    {
        $ones = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];
        $teens = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
        $tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
        $hundreds = ['', 'یکصد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];

        // Remove decimals
        $number = (int) floor($number);
        
        if ($number == 0) {
            return 'صفر';
        }

        $result = '';
        
        // Millions
        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            $result .= self::convertHundreds($millions, $ones, $teens, $tens, $hundreds) . ' میلیون ';
            $number = $number % 1000000;
        }
        
        // Thousands
        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            if ($thousands == 1) {
                $result .= 'هزار ';
            } else {
                $result .= self::convertHundreds($thousands, $ones, $teens, $tens, $hundreds) . ' هزار ';
            }
            $number = $number % 1000;
        }
        
        // Hundreds, tens, ones
        if ($number > 0) {
            $result .= self::convertHundreds($number, $ones, $teens, $tens, $hundreds);
        }
        
        return trim($result) . ' تومان';
    }

    /**
     * Convert a three-digit number to Persian words
     * 
     * @param int $number
     * @param array $ones
     * @param array $teens
     * @param array $tens
     * @param array $hundreds
     * @return string
     */
    private static function convertHundreds(int $number, array $ones, array $teens, array $tens, array $hundreds): string
    {
        if ($number == 0) {
            return '';
        }

        $result = '';
        
        // Hundreds
        if ($number >= 100) {
            $hundred = (int) floor($number / 100);
            $result .= $hundreds[$hundred] . ' ';
            $number = $number % 100;
        }
        
        // Tens and ones
        if ($number >= 20) {
            $ten = (int) floor($number / 10);
            $result .= $tens[$ten];
            $one = $number % 10;
            if ($one > 0) {
                $result .= ' و ' . $ones[$one];
            }
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10];
        } elseif ($number > 0) {
            $result .= $ones[$number];
        }
        
        return trim($result);
    }
}
