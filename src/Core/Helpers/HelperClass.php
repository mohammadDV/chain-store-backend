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

        // Billions (handle separately to avoid index out of bounds)
        if ($number >= 1000000000) {
            $billions = (int) floor($number / 1000000000);
            // Handle billions up to 999 billion
            if ($billions >= 1000) {
                // Split into thousands of billions and remaining billions
                $billionsThousands = (int) floor($billions / 1000);
                $billionsRemainder = $billions % 1000;

                if ($billionsThousands == 1) {
                    $result .= 'یک هزار';
                } else {
                    $result .= self::convertHundreds($billionsThousands, $ones, $teens, $tens, $hundreds) . ' هزار';
                }

                if ($billionsRemainder > 0) {
                    $result .= ' و ' . self::convertHundreds($billionsRemainder, $ones, $teens, $tens, $hundreds);
                }
                $result .= ' میلیارد';
            } else {
                $result .= self::convertHundreds($billions, $ones, $teens, $tens, $hundreds) . ' میلیارد';
            }
            $number = $number % 1000000000;
            // Add connector if there are remaining parts
            if ($number > 0) {
                $result .= ' و ';
            }
        }

        // Millions (ensure we only process if less than 1000 to avoid index issues)
        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            // Ensure millions is less than 1000 to avoid array index issues
            if ($millions < 1000) {
                if ($millions == 1) {
                    $result .= 'یک میلیون';
                } else {
                    $result .= self::convertHundreds($millions, $ones, $teens, $tens, $hundreds) . ' میلیون';
                }
            } else {
                // Handle millions >= 1000 (e.g., 1500 million = 1.5 billion)
                // This should not happen in normal cases, but handle it safely
                $millionsThousands = (int) floor($millions / 1000);
                $millionsRemainder = $millions % 1000;

                if ($millionsThousands == 1) {
                    $result .= 'یک هزار';
                } else {
                    $result .= self::convertHundreds($millionsThousands, $ones, $teens, $tens, $hundreds) . ' هزار';
                }

                if ($millionsRemainder > 0) {
                    $result .= ' و ' . self::convertHundreds($millionsRemainder, $ones, $teens, $tens, $hundreds);
                }
                $result .= ' میلیون';
            }
            $number = $number % 1000000;
            // Add connector if there are remaining parts
            if ($number > 0) {
                $result .= ' و ';
            }
        }

        // Thousands
        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            if ($thousands == 1) {
                $result .= 'هزار';
            } else {
                $result .= self::convertHundreds($thousands, $ones, $teens, $tens, $hundreds) . ' هزار';
            }
            $number = $number % 1000;
            // Add connector if there are remaining parts
            if ($number > 0) {
                $result .= ' و ';
            }
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
     * @param int $number (must be less than 1000)
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

        // Safety check: ensure number is less than 1000 to avoid array index issues
        $number = $number % 1000;

        $result = '';

        // Hundreds
        if ($number >= 100) {
            $hundred = (int) floor($number / 100);
            // Safety check: ensure index exists in array
            if (isset($hundreds[$hundred])) {
                $result .= $hundreds[$hundred];
            }
            $number = $number % 100;
            // Add connector if there are tens or ones remaining
            if ($number > 0) {
                $result .= ' و ';
            }
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
