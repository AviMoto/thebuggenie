<?php

    /**
     * I18n class
     *
     * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
     * @version 3.1
     * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
     * @package thebuggenie
     * @subpackage core
     */

    /**
     * I18n class
     *
     * @package thebuggenie
     * @subpackage core
     */
    class TBGI18n
    {

        protected $_strings = null;

        protected $_missing_strings = array();

        protected $_language = null;

        protected $_charset = 'utf-8';

        protected $_datetime_formats = array();

        public static function getTimezones()
        {
            $list = DateTimeZone::listAbbreviations();
            $idents = DateTimeZone::listIdentifiers();

            $data = $offset = $added = array();
            foreach ($list as $info)
            {
                foreach ($info as $zone)
                {
                    if (!empty($zone['timezone_id'])
                            AND
                            !in_array($zone['timezone_id'], $added)
                            AND
                            in_array($zone['timezone_id'], $idents))
                    {
                        $z = new DateTimeZone($zone['timezone_id']);
                        $c = new DateTime(null, $z);
                        $zone['time'] = $c->format('H:i a');
                        $data[] = $zone;
                        $offset[] = $z->getOffset($c);
                        $added[] = $zone['timezone_id'];
                    }
                }
            }

            array_multisort($offset, SORT_ASC, $data);
            $options = array();
            foreach ($data as $row)
            {
                $options[$row['timezone_id']] = self::formatOffset($row['offset']) . ' ('.$row['timezone_id'].')';
            }

            return $options;
        }

        protected static function formatOffset($offset)
        {
            $hours = $offset / 3600;
            $remainder = $offset % 3600;
            $sign = $hours > 0 ? '+' : '-';
            $hour = (int) abs($hours);
            $minutes = (int) abs($remainder / 60);

            if ($hour == 0 AND $minutes == 0)
            {
                $sign = ' ';
            }
            return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT)
                    . ':' . str_pad($minutes, 2, '0');
        }

        public function __construct($language)
        {
            if (!file_exists($this->getStringsFilename($language)))
            {
                TBGLogging::log('Selected language not available, trying "en_US" as a last attempt', 'i18n', TBGLogging::LEVEL_NOTICE);
                $this->_language = 'en_US';
                if (!file_exists($this->getStringsFilename($this->_language)))
                {
                    throw new Exception('The selected language is not available');
                }
            }
            $this->_language = $language;
        }

        public function getStringsFilename($language = null)
        {
            $language = ($language === null) ? $this->_language : $language;
            return THEBUGGENIE_PATH . 'i18n' . DS . $language . DS . 'strings.xlf';
        }

        public function initialize()
        {
            $filename = THEBUGGENIE_PATH . 'i18n' . DS . $this->_language . DS . 'initialize.inc.php';
            if (file_exists($filename))
            {
                TBGLogging::log("Initiating with file '{$filename}", 'i18n');
                include $filename;
            }
            if ($this->_strings === null)
            {
                if (TBGContext::getCache()->fileHas(TBGCache::KEY_I18N . 'strings_' . $this->_language, false))
                {
                    $strings = TBGContext::getCache()->fileGet(TBGCache::KEY_I18N . 'strings_' . $this->_language, false);
                    $this->_strings = (is_array($strings) && !empty($strings)) ? $strings : null;
                }
                if ($this->_strings === null)
                {
                    $this->loadStrings();
                    foreach (array_keys(TBGContext::getModules()) as $module_name)
                    {
                        $this->loadStrings($module_name);
                    }
                    if (is_array($this->_strings) && !empty($this->_strings)) 
                    {
                        TBGContext::getCache()->fileAdd(TBGCache::KEY_I18N . 'strings_' . $this->_language, $this->_strings, false);
                    }
                }
            }
        }

        public function setLanguage($language)
        {
            if ($language != $this->_language)
            {
                $this->_language = $language;
                $this->initialize();
            }
        }

        public function setCharset($charset)
        {
            $this->_charset = $charset;
        }

        public function getCurrentLanguage()
        {
            return $this->_language;
        }

        public function getCharset()
        {
            if (TBGContext::isInstallmode()) return $this->_charset;
            return (TBGSettings::get('charset') != '') ? TBGSettings::get('charset') : $this->_charset;
        }

        public function getLangCharset()
        {
            return $this->_charset;
        }

        public function loadModuleStrings($module)
        {
            $this->loadStrings($module);
        }

        protected function loadStrings($module = null)
        {
            if ($this->_strings === null) $this->_strings = array();
            $filename = '';
            if ($module !== null)
            {
                if (file_exists(THEBUGGENIE_PATH . 'i18n' . DS . $this->_language . DS . "{$module}.xlf"))
                    $filename = THEBUGGENIE_PATH . 'i18n' . DS . $this->_language . DS . "{$module}.xlf";
                else
                    $filename = THEBUGGENIE_MODULES_PATH . $module . DS . 'i18n' . DS . $this->_language . DS . "strings.xlf";
            }
            else
            {
                $filename = $this->getStringsFilename();
            }

            if (file_exists($filename))
            {
                TBGLogging::log("Loading strings from file '{$filename}", 'i18n');
                $xliff_dom = new DOMDocument();
                $xliff_dom->loadXML(file_get_contents($filename));
                $trans_units = $xliff_dom->getElementsByTagName('trans-unit');
                foreach ($trans_units as $trans_unit)
                {
                    $source_tag = $trans_unit->getElementsByTagName('source');
                    $target_tag = $trans_unit->getElementsByTagName('target');
                    if (is_object($source_tag) && is_object($source_tag->item(0)) && is_object($target_tag) && is_object($target_tag->item(0)))
                    {
                        $this->addString($source_tag->item(0)->nodeValue, $target_tag->item(0)->nodeValue);
                    }
                }
            }
            else
            {
                $message = 'Could not find language file ' . $filename;
                TBGLogging::log($message, 'i18n', TBGLogging::LEVEL_NOTICE);
            }
        }

        public function addString($key, $translation)
        {
            $this->_strings[$key] = $translation;
        }

        public function addStrings($strings)
        {
            if (is_array($strings))
            {
                foreach ($strings as $key => $translation)
                {
                    $this->_strings[$key] = $translation;
                }
            }
        }

        public static function getLanguages()
        {
            $retarr = array();
            $cp_handle = opendir(THEBUGGENIE_PATH . 'i18n');
            while ($classfile = readdir($cp_handle))
            {
                if (mb_strstr($classfile, '.') == '' && file_exists(THEBUGGENIE_PATH . 'i18n/' . $classfile . '/language'))
                {
                    $retarr[$classfile] = file_get_contents(THEBUGGENIE_PATH . 'i18n/' . $classfile . '/language');
                }
            }

            return $retarr;
        }

        public function hasTranslatedTemplate($template, $is_component = false)
        {
            if (mb_strpos($template, '/'))
            {
                $templateinfo = explode('/', $template);
                $module = $templateinfo[0];
                $templatefile = ($is_component) ? '_' . $templateinfo[1] . '.inc.php' : $templateinfo[1] . '.' . TBGContext::getRequest()->getRequestedFormat() . '.php';
            }
            else
            {
                $module = TBGContext::getRouting()->getCurrentRouteModule();
                $templatefile = ($is_component) ? '_' . $template . '.inc.php' : $template . '.' . TBGContext::getRequest()->getRequestedFormat() . '.php';
            }
            if (file_exists(THEBUGGENIE_MODULES_PATH . $module . DS . 'i18n' . DS . $this->_language . DS . 'templates' . DS . $templatefile))
            {
                return THEBUGGENIE_MODULES_PATH . $module . DS . 'i18n' . DS . $this->_language . DS . 'templates' . DS . $templatefile;
            }
            elseif (file_exists(THEBUGGENIE_PATH . 'i18n' . DS . $this->getCurrentLanguage() . DS . 'templates' . DS . $module . DS . $templatefile))
            {
                return THEBUGGENIE_PATH . 'i18n' . DS . $this->getCurrentLanguage() . DS . 'templates' . DS . $module . DS . $templatefile;
            }
            return false;
        }

        public function __($text, $replacements = array(), $html_decode = false)
        {
            if (isset($this->_strings[$text]))
            {
                $retstring = $this->_strings[$text];
            }
            else
            {
                $event = TBGEvent::createNew('core', 'TBGI18n::__()', $this, compact('text', 'replacements', 'html_decode'))->trigger();
                if ($event->isProcessed())
                {
                    return $event->getReturnValue();
                }
                else
                {
                    $retstring = $text;
                    TBGLogging::log('The text "' . $text . '" does not exist in list of translated strings.', 'i18n');
                    $this->_missing_strings[$text] = true;
                }
            }
            if (!empty($replacements))
            {
                $tmp = array();
                foreach ($replacements as $key => $value)
                {
                    $tmp[$key] = $value;
                    $retstring = str_replace(array_keys($tmp), array_values($tmp), $retstring);
                }
            }
            if ($html_decode) {
                $retstring = html_entity_decode($retstring);
            }
            return $retstring;
        }

        /**
         * Set local date and time formats
         *
         * @param $formats array list of applicable formats for this local
         *
         */
        public function setDateTimeFormats($formats)
        {
            if(is_array($formats))
            {
                $this->_datetime_formats = $formats;
            }
        }

        /**
         * Return localized date and time format
         * @see http://php.net/manual/en/function.date.php
         *
         * @param $id integer ID of format
         *
         * @return string
         *
         */
        public function getDateTimeFormat($id)
        {
            if(array_key_exists($id, $this->_datetime_formats))
            {
                 return $this->_datetime_formats[$id];
            }
            switch ($id)
            {
                case 1 : // 14:45 - Thu Dec 30, 2010
                    $format = '%H:%M - %a %b %d, %Y';
                    break;
                case 2 : // 14:45 - Thu 30.m, 2010
                    $format = '%H:%M - %a %d.m, %Y';
                    break;
                case 3 : // Thu Dec 30 14:45
                    $format = '%a %b %d %H:%M';
                    break;
                case 4 : // Dec 30 14:45
                    $format = '%b %d %H:%M';
                    break;
                case 5 : // December 30, 2010
                    $format = '%B %d, %Y';
                    break;
                case 6 : // December 30, 2010 (14:45)
                    $format = '%B %d, %Y (%H:%M)';
                    break;
                case 7 : // Thursday 30 December, 2010 (14:45)
                    $format = '%A %d %B, %Y (%H:%M)';
                    break;
                case 8 : // Dec 30, 2010 14:45
                    $format = '%b %d, %Y %H:%M';
                    break;
                case 9 : // Dec 30, 2010 - 14:45
                    $format = '%b %d, %Y - %H:%M';
                    break;
                case 10 : // Dec 30, 2010 (14:45)
                    $format = '%b %d, %Y (%H:%M)';
                    break;
                case 11 : // December
                    $format = '%B';
                    break;
                case 12 : // Dec 30
                    $format = '%b %d';
                    break;
                case 13 : // Thu
                    $format = '%a';
                    break;
                case 14 : // 14:45
                    $format = '%H:%M';
                    break;
                case 15 : // Dec 30, 2010
                    $format = '%b %d, %Y';
                    break;
                case 16 : // 14h 45m
                    $format = '%Gh %im';
                    break;
                case 17 : // Thu, 30 December 2010 14:45:45 GMT
                    $format = '%a, %d %b %Y %H:%M:%S GMT';
                    break;
                case 18 : // Thu, 30 December 2010 14:45:45 GMT
                    $format = '%Y-%M-%D';
                    break;
                default : // local server setting
                    $format = '%c';
            }
            return $format;
        }

    }
