<?php
/**
 * SAPE.ru - Интеллектуальная система купли-продажи ссылок
 *
 * PHP-клиент
 *
 * Вебмастеры! Не нужно ничего менять в этом файле!
 * Все настройки - через параметры при вызове кода.
 *
 * Подробную информацию по добавлению сайта в систему,
 * установки кода, а так же по всему остальным вопросам
 * Вы можете найти здесь:
 * @link http://help.sape.ru/sape/faq/27
 * @link http://help.sape.ru/articles/faq/1041
 *
 */


namespace  Drupal\sape\vendor\sape;

/**
 * Класс для работы с контекстными ссылками
 */
class SAPE_context extends SAPE_base
{

    protected $_words       = array();
    protected $_words_page  = array();
    protected $_user_agent  = 'SAPE_Context PHP';
    protected $_filter_tags = array('a', 'textarea', 'select', 'script', 'style', 'label', 'noscript', 'noindex', 'button');

    protected $_debug_actions = array();

    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->_load_data();
    }

    /**
     * Начать сбор дебаг-информации
     */
    protected function _debug_action_start()
    {
        if (!$this->_debug) {
            return;
        }

        $this->_debug_actions   = array();
        $this->_debug_actions[] = $this->_get_full_user_agent_string();
    }

    /**
     * Записать строку дебаг-информацию
     *
     * @param        $data
     * @param string $key
     */
    protected function _debug_action_append($data, $key = '')
    {
        if (!$this->_debug) {
            return;
        }

        if (!empty($key)) {
            $this->_debug_actions[] = array($key => $data);
        } else {
            $this->_debug_actions[] = $data;
        }
    }

    /**
     * Вывод дебаг-информации
     *
     * @return string
     */
    protected function _debug_action_output()
    {

        if (!$this->_debug || empty($this->_debug_actions)) {
            return '';
        }

        $debug_info = $this->_debug_output($this->_debug_actions);

        $this->_debug_actions = array();

        return $debug_info;
    }

    /**
     * Замена слов в куске текста и обрамляет его тегами sape_index
     */
    public function replace_in_text_segment($text)
    {

        $this->_debug_action_start();
        $this->_debug_action_append('START: replace_in_text_segment()');
        $this->_debug_action_append($text, 'argument for replace_in_text_segment');

        if (count($this->_words_page) > 0) {

            $source_sentences = array();

            //Создаем массив исходных текстов для замены
            foreach ($this->_words_page as $n => $sentence) {
                //Заменяем все сущности на символы
                $special_chars = array(
                    '&amp;'  => '&',
                    '&quot;' => '"',
                    '&#039;' => '\'',
                    '&lt;'   => '<',
                    '&gt;'   => '>'
                );
                $sentence      = strip_tags($sentence);
                $sentence      = strip_tags($sentence);
                $sentence      = str_replace(array_keys($special_chars), array_values($special_chars), $sentence);

                //Преобразуем все спец символы в сущности
                $htsc_charset = empty($this->_charset) ? 'windows-1251' : $this->_charset;
                $quote_style  = ENT_COMPAT;
                if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                    $quote_style = ENT_COMPAT | ENT_HTML401;
                }

                $sentence = htmlspecialchars($sentence, $quote_style, $htsc_charset);

                //Квотируем
                $sentence      = preg_quote($sentence, '/');
                $replace_array = array();
                if (preg_match_all('/(&[#a-zA-Z0-9]{2,6};)/isU', $sentence, $out)) {
                    for ($i = 0; $i < count($out[1]); $i++) {
                        $unspec                 = $special_chars[$out[1][$i]];
                        $real                   = $out[1][$i];
                        $replace_array[$unspec] = $real;
                    }
                }
                //Заменяем сущности на ИЛИ (сущность|символ)
                foreach ($replace_array as $unspec => $real) {
                    $sentence = str_replace($real, '((' . $real . ')|(' . $unspec . '))', $sentence);
                }
                //Заменяем пробелы на переносы или сущности пробелов
                $source_sentences[$n] = str_replace(' ', '((\s)|(&nbsp;))+', $sentence);
            }

            $this->_debug_action_append($source_sentences, 'sentences for replace');

            //если это первый кусок, то не будем добавлять <
            $first_part = true;
            //пустая переменная для записи

            if (count($source_sentences) > 0) {

                $content   = '';
                $open_tags = array(); //Открытые забаненые тэги
                $close_tag = ''; //Название текущего закрывающего тэга

                //Разбиваем по символу начала тега
                $part = strtok(' ' . $text, '<');

                while ($part !== false) {
                    //Определяем название тэга
                    if (preg_match('/(?si)^(\/?[a-z0-9]+)/', $part, $matches)) {
                        //Определяем название тега
                        $tag_name = strtolower($matches[1]);
                        //Определяем закрывающий ли тэг
                        if (substr($tag_name, 0, 1) == '/') {
                            $close_tag = substr($tag_name, 1);
                            $this->_debug_action_append($close_tag, 'close tag');
                        } else {
                            $close_tag = '';
                            $this->_debug_action_append($tag_name, 'open tag');
                        }
                        $cnt_tags = count($open_tags);
                        //Если закрывающий тег совпадает с тегом в стеке открытых запрещенных тегов
                        if (($cnt_tags > 0) && ($open_tags[$cnt_tags - 1] == $close_tag)) {
                            array_pop($open_tags);

                            $this->_debug_action_append($tag_name, 'deleted from open_tags');

                            if ($cnt_tags - 1 == 0) {
                                $this->_debug_action_append('start replacement');
                            }
                        }

                        //Если нет открытых плохих тегов, то обрабатываем
                        if (count($open_tags) == 0) {
                            //если не запрещенный тэг, то начинаем обработку
                            if (!in_array($tag_name, $this->_filter_tags)) {
                                $split_parts = explode('>', $part, 2);
                                //Перестраховываемся
                                if (count($split_parts) == 2) {
                                    //Начинаем перебор фраз для замены
                                    foreach ($source_sentences as $n => $sentence) {
                                        if (preg_match('/' . $sentence . '/', $split_parts[1]) == 1) {
                                            $split_parts[1] = preg_replace('/' . $sentence . '/', str_replace('$', '\$', $this->_words_page[$n]), $split_parts[1], 1);

                                            $this->_debug_action_append($sentence . ' --- ' . $this->_words_page[$n], 'replaced');

                                            //Если заменили, то удаляем строчку из списка замены
                                            unset($source_sentences[$n]);
                                            unset($this->_words_page[$n]);
                                        }
                                    }
                                    $part = $split_parts[0] . '>' . $split_parts[1];
                                    unset($split_parts);
                                }
                            } else {
                                //Если у нас запрещеный тэг, то помещаем его в стек открытых
                                $open_tags[] = $tag_name;

                                $this->_debug_action_append($tag_name, 'added to open_tags, stop replacement');
                            }
                        }
                    } elseif (count($open_tags) == 0) {
                        //Если нет названия тега, то считаем, что перед нами текст
                        foreach ($source_sentences as $n => $sentence) {
                            if (preg_match('/' . $sentence . '/', $part) == 1) {
                                $part = preg_replace('/' . $sentence . '/', str_replace('$', '\$', $this->_words_page[$n]), $part, 1);

                                $this->_debug_action_append($sentence . ' --- ' . $this->_words_page[$n], 'replaced');

                                //Если заменили, то удаляем строчку из списка замены,
                                //чтобы было можно делать множественный вызов
                                unset($source_sentences[$n]);
                                unset($this->_words_page[$n]);
                            }
                        }
                    }

                    //Если это первая часть, то не выводим <
                    if ($first_part) {
                        $content .= $part;
                        $first_part = false;
                    } else {
                        $content .= '<' . $part;
                    }
                    //Получаем следующу часть
                    unset($part);
                    $part = strtok('<');
                }
                $text = ltrim($content);
                unset($content);
            }
        } else {
            $this->_debug_action_append('No word\'s for page');
        }

        if ($this->_is_our_bot || $this->_force_show_code || $this->_debug) {
            $text = '<sape_index>' . $text . '</sape_index>';
            if (isset($this->_words['__sape_new_url__']) && strlen($this->_words['__sape_new_url__'])) {
                $text .= $this->_words['__sape_new_url__'];
            }
        }

        if (count($this->_words_page) > 0) {
            $this->_debug_action_append($this->_words_page, 'Not replaced');
        }

        $this->_debug_action_append('END: replace_in_text_segment()');

        $text .= $this->_debug_action_output();

        return $text;
    }

    /**
     * Замена слов
     */
    public function replace_in_page($buffer)
    {

        $this->_debug_action_start();
        $this->_debug_action_append('START: replace_in_page()');

        $s_globals = new SAPE_globals();

        if (!$s_globals->page_obligatory_output_shown()
            && isset($this->_page_obligatory_output)
            && !empty($this->_page_obligatory_output)
        ) {

            $split_content = preg_split('/(?smi)(<\/?body[^>]*>)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (count($split_content) == 5) {
                $buffer = $split_content[0] . $split_content[1] . $split_content[2]
                    . (false == $this->_show_counter_separately ? $this->_return_obligatory_page_content() : '')
                    . $split_content[3] . $split_content[4];
                unset($split_content);

                $s_globals->page_obligatory_output_shown(true);
            }
        }

        if (count($this->_words_page) > 0) {
            //разбиваем строку по sape_index
            //Проверяем есть ли теги sape_index
            $split_content = preg_split('/(?smi)(<\/?sape_index>)/', $buffer, -1);
            $cnt_parts     = count($split_content);
            if ($cnt_parts > 1) {
                //Если есть хоть одна пара sape_index, то начинаем работу
                if ($cnt_parts >= 3) {
                    for ($i = 1; $i < $cnt_parts; $i = $i + 2) {
                        $split_content[$i] = $this->replace_in_text_segment($split_content[$i]);
                    }
                }
                $buffer = implode('', $split_content);

                $this->_debug_action_append($cnt_parts, 'Split by Sape_index cnt_parts=');
            } else {
                //Если не нашли sape_index, то пробуем разбить по BODY
                $split_content = preg_split('/(?smi)(<\/?body[^>]*>)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
                //Если нашли содержимое между body
                if (count($split_content) == 5) {
                    $split_content[0] = $split_content[0] . $split_content[1];
                    $split_content[1] = $this->replace_in_text_segment($split_content[2]);
                    $split_content[2] = $split_content[3] . $split_content[4];
                    unset($split_content[3]);
                    unset($split_content[4]);
                    $buffer = $split_content[0] . $split_content[1] . $split_content[2];

                    $this->_debug_action_append('Split by BODY');
                } else {
                    //Если не нашли sape_index и не смогли разбить по body
                    $this->_debug_action_append('Cannot split by BODY');
                }
            }
        } else {
            if (!$this->_is_our_bot && !$this->_force_show_code && !$this->_debug) {
                $buffer = preg_replace('/(?smi)(<\/?sape_index>)/', '', $buffer);
            } else {
                if (isset($this->_words['__sape_new_url__']) && strlen($this->_words['__sape_new_url__'])) {
                    $buffer .= $this->_words['__sape_new_url__'];
                }
            }

            $this->_debug_action_append('No word\'s for page');
        }

        $this->_debug_action_append('STOP: replace_in_page()');
        $buffer .= $this->_debug_action_output();

        return $buffer;
    }

    protected function _get_db_file()
    {
        if ($this->_multi_site) {
            return dirname(__FILE__) . '/' . $this->_host . '.words' . $this->_get_save_filename_prefix() . '.db';
        } else {
            return dirname(__FILE__) . '/words' . $this->_get_save_filename_prefix() . '.db';
        }
    }

    protected function _get_meta_file()
    {
        if ($this->_multi_site) {
            return dirname(__FILE__) . '/' . $this->_host . '.words.meta.db';
        } else {
            return dirname(__FILE__) . '/words.meta.db';
        }
    }

    protected function _get_dispenser_path()
    {
        return '/code_context.php?user=' . _SAPE_USER . '&host=' . $this->_host;
    }

    protected function _set_data($data)
    {
        $this->_words = $data;
        if (@array_key_exists($this->_request_uri, $this->_words) && is_array($this->_words[$this->_request_uri])) {
            $this->_words_page = $this->_words[$this->_request_uri];
        }

        //Есть ли обязательный вывод
        if (isset($this->_words['__sape_page_obligatory_output__'])) {
            $this->_page_obligatory_output = $this->_words['__sape_page_obligatory_output__'];
        }
    }

    protected function _uncode_data($data)
    {
        if ($this->_format == 'php-require') {
            $data1 = str_replace('<?php return ', '', $data);
            eval('$data = ' . $data1 . ';');
            return $data;
        }

        return @unserialize($data);
    }

    protected function _code_data($data)
    {
        if ($this->_format == 'php-require') {
            return var_export($data, true);
        }

        return @serialize($data);
    }

    protected function _save_data($data, $filename = '')
    {
        if ($this->_split_data_file) {
            if ($this->_multi_site) {
                $directory = dirname(__FILE__) . '/' . $this->_host . '/';
            } else {
                $directory = dirname(__FILE__) . '/';
            }
            $hashArray = array();
            $data = $this->_uncode_data($data);
            foreach ($data as $url => $item) {
                if (preg_match('/\_\_.+\_\_/mu', $url)) {
                    $currentFile = 'words.meta.db';
                } else {
                    $currentFile = 'words.' . crc32($url) % 100 . '.db';
                }
                $hashArray[$currentFile][$url] = $item;
            }
            foreach ($hashArray as $file => $array) {
                $this->_write($directory . $file, $this->_code_data($array));
            }
        } else {
            parent::_save_data($data, $filename = '');
        }
    }
}
