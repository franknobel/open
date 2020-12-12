<?php

namespace Indexcall\Common;

use Bitrix\Main\Application,
    Bitrix\Main\Loader;

class Utils {

    public static function ResizeImage($image_id, $width, $height, $contain = false, $string = true) {
        $result = "";
        if (!$image_id) {
            $image_id = $GLOBALS["SETTINGS"]["NO_IMAGE"];
            $koef = 1;
            $width = round($width * $koef, 0);
            $height = round($height * $koef, 0);
        }
        $arFile = \CFile::MakeFileArray($image_id);
        if ($contain) {
            $size_image = getimagesize($arFile["tmp_name"]);
            $koef_width = $size_image[0] / $width;
            $koef_height = $size_image[1] / $height;
            if ($koef_width > $koef_height) {
                $new_size = round($size_image[0] / $koef_height + 0.5);
                $size_array = array("width" => $new_size, "height" => $height);
            } else {
                $new_size = round($size_image[1] / $koef_width + 0.5);
                $size_array = array("width" => $width, "height" => $new_size);
            }
        } else {
            $size_array = array("width" => $width, "height" => $height);
        }
        $file = \CFile::ResizeImageGet($image_id, $size_array, BX_RESIZE_IMAGE_PROPORTIONAL, true, array(), false, 90);
        if (!$string) {
            $result = array(
                "SRC" => $file["src"],
                "W" => $file["width"],
                "H" => $file["height"]
            );
            if (!$result["W"]) {
                $result["W"] = $width;
            }
        } else {
            $result = 'src="' . $file["src"] . '"';
            if ($file["width"]) {
                $result .= ' width="' . $file["width"] . '"';
            } else {
                $result .= ' width="' . $width . '"';
            }
            if ($file["height"]) {
                $result .= ' height="' . $file["height"] . '"';
            }
        }
        return $result;
    }

    public static function GetDateStr($date) {
        $months = array("пьянварь", "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря");
        $cur_date = explode(".", $date);
        $cool_date = $cur_date[0] . " " . $months[(int)$cur_date[1]] . " " . $cur_date[2];
        return $cool_date;
    }

    public static function GetDate($date, $year = true, $full = true) {
        $months = array("пьянварь", "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря");
        $cur_date = explode(".", $date);
        if ($full) {
            $cool_date = $cur_date[0] . " " . $months[(int)$cur_date[1]];
        } else {
            $cool_date = IntVal($cur_date[0]) . " " . $months[(int)$cur_date[1]];
        }
        if ($year) {
            $cool_date .= " " . $cur_date[2];
        }
        return $cool_date;
    }

    public static function GetRusWeek($date, $shirt = true, $format = "normal") {
        $what_show = "SHIRT";
        if (!$shirt) {
            $what_show = "FOOL";
        }
        $weekdays = array(
            "FOOL" => array(
                "Воскресенье",
                "Понедельник",
                "Вторник",
                "Среда",
                "Четверг",
                "Пятница",
                "Суббота"
            ),
            "SHIRT" => array(
                "Вс",
                "Пн",
                "Вт",
                "Ср",
                "Чт",
                "Пт",
                "Сб"
            )
        );
        $cur_date = explode(".", $date);
        $cur_week_day = date("w", mktime(0, 0, 0, $cur_date[1], $cur_date[0], $cur_date[2]));
        $temp_result = $weekdays[$what_show][$cur_week_day];
        $result = $temp_result;
        if ($format == "lower") {
            $result = strtolower($temp_result);
        }
        if ($format == "upper") {
            $result = strtoupper($temp_result);
        }
        return $result;
    }

    public static function GetWordBySum($num) {

        # Все варианты написания чисел прописью от 0 до 999 скомпануем в один небольшой массив
        $m = array(
            array('ноль'),
            array('-', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
            array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'),
            array('-', '-', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'),
            array('-', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'),
            array('-', 'одна', 'две')
        );

        # Все варианты написания разрядов прописью скомпануем в один небольшой массив
        $r = array(
            array('...ллион', '', 'а', 'ов'), // используется для всех неизвестно больших разрядов
            array('тысяч', 'а', 'и', ''),
            array('миллион', '', 'а', 'ов'),
            array('миллиард', '', 'а', 'ов'),
            array('триллион', '', 'а', 'ов'),
            array('квадриллион', '', 'а', 'ов'),
            array('квинтиллион', '', 'а', 'ов')
            // ,array(... список можно продолжить
        );

        if ($num == 0) return $m[0][0]; # Если число ноль, сразу сообщить об этом и выйти
        $o = array(); # Сюда записываем все получаемые результаты преобразования

        # Разложим исходное число на несколько трехзначных чисел и каждое полученное такое число обработаем отдельно
        foreach (array_reverse(str_split(str_pad($num, ceil(strlen($num) / 3) * 3, '0', STR_PAD_LEFT), 3)) as $k => $p) {
            $o[$k] = array();

            # Алгоритм, преобразующий трехзначное число в строку прописью
            foreach ($n = str_split($p) as $kk => $pp)
                if (!$pp) continue; else
                    switch ($kk) {
                        case 0:
                            $o[$k][] = $m[4][$pp];
                            break;
                        case 1:
                            if ($pp == 1) {
                                $o[$k][] = $m[2][$n[2]];
                                break 2;
                            } else$o[$k][] = $m[3][$pp];
                            break;
                        case 2:
                            if (($k == 1) && ($pp <= 2)) $o[$k][] = $m[5][$pp]; else$o[$k][] = $m[1][$pp];
                            break;
                    }
            $p *= 1;
            if (!$r[$k])
                $r[$k] = reset($r);

            # Алгоритм, добавляющий разряд, учитывающий окончание руского языка
            if ($p && $k) switch (true) {
                case preg_match("/^[1]$|^\\d*[0,2-9][1]$/", $p):
                    $o[$k][] = $r[$k][0] . $r[$k][1];
                    break;
                case preg_match("/^[2-4]$|\\d*[0,2-9][2-4]$/", $p):
                    $o[$k][] = $r[$k][0] . $r[$k][2];
                    break;
                default:
                    $o[$k][] = $r[$k][0] . $r[$k][3];
                    break;
            }
            $o[$k] = implode(' ', $o[$k]);
        }
        return implode(' ', array_reverse($o));
    }

    public static function GetWordByNumber($number, $words, $special = false) {
        if(!$special || $number <= 10 || $number >= 20) {
            $needed = substr($number, strlen($number) - 1);
            if ($needed == 0 || $needed >= 5 || $number == 11) {
                $word = $words[0];
            } elseif ($needed == 1) {
                $word = $words[1];
            } else {
                $word = $words[2];
            }
        } else {
            $word = $words[0];
        }

        return $word;
    }

    public static function Money($price, $digits = 0) {
        $more = $price - intval($price);
        if ($more == 0) {
            $digits = 0;
        }
        return number_format($price, $digits, ".", " ");
    }

    public static function GetSize($number) {
        $label = array("б", "Кб", "Мб", "Гб");
        $step = 0;
        while ($number / 1024 > 1024) {
            $step++;
            $number /= 1024;
        }
        $step++;
        $number /= 1024;
        return round($number, 1) . " " . $label[$step];
    }

    public static function GetCookie($cookieName) {
        return Application::getInstance()->getContext()->getRequest()->getCookie($cookieName);
    }

    public static function GetYouTubeLink ($fullLink, $returnPicture = false) {
        $regExp = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/';
        preg_match($regExp, $fullLink, $matches);
        if($matches && strlen($matches[2]) == 11) {
            if(!$returnPicture) {
                return 'https://www.youtube.com/embed/' . $matches[2];
            } else {
                return ['https://img.youtube.com/vi/' . $matches[2] . '/sddefault.jpg', $matches[2]];
            }
        }

        return false;
    }

    public static function GetYoutubeDuration ($vid) {
        $videoDetails = file_get_contents('https://www.googleapis.com/youtube/v3/videos?id=' . $vid . '&part=contentDetails,statistics&key=AIzaSyAj2KfOmAbwdV16eFj_hdFbv17S4zkmDRc');
        $VidDuration = json_decode($videoDetails, true);
        foreach ($VidDuration['items'] as $vidTime) {
            $VidDuration = $vidTime['contentDetails']['duration'];
        }
        $pattern = '/PT(\d+)M(\d+)S/';
        preg_match($pattern, $VidDuration, $matches);
        $seconds = $matches[1] * 60 + $matches[2];

        return $seconds;
    }

    public static function GetDefaultLanguages () {
        Loader::includeModule('iblock');
        $languages = [];

        $userLanguages = [];
        if (($list = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']))) {
            if (preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/', $list, $list)) {
                $userLanguages = array_combine($list[1], $list[2]);
                foreach ($userLanguages as $n => $v)
                    $userLanguages[$n] = $v ? $v : 1;
                arsort($userLanguages, SORT_NUMERIC);
            }
        } else $userLanguages = [];

        if(sizeof($userLanguages) > 0) {
            $fullLanguageKey = key($userLanguages);
            if(strpos($fullLanguageKey, '-') === false) {
                $languageCode = $fullLanguageKey;
            } else {
                $languageCode = substr($fullLanguageKey, 0, strpos($fullLanguageKey, '-'));
            }

            $arItems = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => LANGUAGES_IBLOCK_ID, 'ACTIVE' => 'Y', '%CODE' => $languageCode],
                false,
                false,
                ['ID', 'NAME', 'PROPERTY_DEFAULT']
            );

            if($item = $arItems->GetNext()) {
                $languages[] = $item['ID'];
            }

        }

        if(sizeof($languages) == 0) {
            $arItems = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => LANGUAGES_IBLOCK_ID, 'ACTIVE' => 'Y', 'PROPERTY_DEFAULT_VALUE' => 'да'],
                false,
                false,
                ['ID', 'NAME', 'PROPERTY_DEFAULT']
            );

            while($item = $arItems->GetNext()) {
                $languages[] = $item['ID'];
            }
        }


        return $languages;
    }

    public static function GetIblockIdByCode($code) {
        if (\CModule::IncludeModule('iblock')){
            $arIblock = \CIBlock::GetList([], ['=CODE' => $code], false);
            if($iblock = $arIblock->Fetch())
                return $iblock["ID"];
        }
        return 0;
    }

    public static function GetIblockSectionIdByCode($IBlock_ID, $code) {
        $ID = 0;
        if (\CModule::IncludeModule('iblock')){
            $rsSections = \CIBlockSection::GetList([],['IBLOCK_ID' => $IBlock_ID, '=CODE' => $code]);
            if ($arSection = $rsSections->Fetch())
                $ID = $arSection['ID'];
        }
        return $ID;
    }

    public static function GetDomainAge($domainTimestamp) {
        if ($domainTimestamp) {
            $currentDate = new \DateTime();
            $domainDate = new \DateTime('@' . $domainTimestamp);
            $interval = $domainDate->diff($currentDate);

            if ($interval->y == 0) {
                return 'меньше года';
            }

            return $interval->y;
        }

        return '';
    }

    public static function IsLink($value) {
        $matches = false;
        preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)[a-zа-я0-9]+([\-\.]{1}[a-zа-я0-9]+)*\.([a-z]{2,5}|рф)(:[0-9]{1,5})?(\/.*)?$/iu', $value, $matches);
        if (sizeof($matches) > 0) {
            return true;
        }

        return false;
    }

    public static function GetFormatTime($seconds) {
        $result = gmdate("H:i:s", $seconds);
        if (substr($result,0, 2) == '00') {
            return substr($result, 3);
        }

        return $result;
    }

    public static function GetProtocol() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    }

    public static function GenerateGradient($hardColors) {
        $result = [
            'start' => '#',
            'finish' => '#'
        ];

        $letters = '0123456789ABCDEF';

        if($hardColors) {
            $letters = '0123456789AB';
        }

        for($i = 0; $i <= 2; $i++) {
            $result['start'] .= 'F' .  $letters[rand(0, strlen($letters) - 1)];
            $result['finish'] .= 'F' .  $letters[rand(0, strlen($letters) - 1)];
        }

        return $result;
    }
}