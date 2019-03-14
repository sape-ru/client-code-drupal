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
 * Основной класс, выполняющий всю рутину
 */
class SAPE_base
{
    private static $_tables = array();

    /**
     * @return Op_Db_Table_Abstract
     */
    public static function getInstance($options)
    {
        $class = get_called_class();
        if (empty(self::$_tables[$class])) {
            self::$_tables[$class] = new $class($options);
        }

        return self::$_tables[$class];
    }

    protected $_version = '1.4.3';

    protected $_verbose = false;

    /**
     * Кодировка сайта
     * @link http://www.php.net/manual/en/function.iconv.php
     * @var string
     */
    protected $_charset = '';

    protected $_sape_charset = '';

    protected $_server_list = array('dispenser-01.saperu.net', 'dispenser-02.saperu.net');

    /**
     * Пожалейте наш сервер :о)
     * @var int
     */
    protected $_cache_lifetime = 3600;

    /**
     * Если скачать базу ссылок не удалось, то следующая попытка будет через столько секунд
     * @var int
     */
    protected $_cache_reloadtime = 600;

    protected $_errors = array();

    protected $_host = '';

    protected $_request_uri = '';

    protected $_multi_site = false;

    /**
     * Способ подключения к удалённому серверу [file_get_contents|curl|socket]
     * @var string
     */
    protected $_fetch_remote_type = '';

    /**
     * Сколько ждать ответа
     * @var int
     */
    protected $_socket_timeout = 6;

    protected $_force_show_code = false;

    /**
     * Если наш робот
     * @var bool
     */
    protected $_is_our_bot = false;

    protected $_debug                   = false;
    protected $_file_contents_for_debug = array();

    /**
     * Регистронезависимый режим работы, использовать только на свой страх и риск
     * @var bool
     */
    protected $_ignore_case = false;

    /**
     * Путь к файлу с данными
     * @var string
     */
    protected $_db_file = '';

    /**
     * Формат запроса. serialize|php-require
     * @var string
     */
    protected $_format = 'serialize';

    /**
     * Флаг для разбиения links.db по отдельным файлам.
     * @var bool
     */
    protected $_split_data_file = true;
    /**
     * Откуда будем брать uri страницы: $_SERVER['REQUEST_URI'] или getenv('REQUEST_URI')
     * @var bool
     */
    protected $_use_server_array = false;

    /**
     * Показывать ли код js отдельно от выводимого контента
     *
     * @var bool
     */
    protected $_show_counter_separately = false;

    protected $_force_update_db = false;

    protected $_user_agent = '';

    public function __construct($options = null)
    {

        // Поехали :o)

        $host = '';

        if (is_array($options)) {
            if (isset($options['host'])) {
                $host = $options['host'];
            }
        } elseif (strlen($options)) {
            $host    = $options;
            $options = array();
        } else {
            $options = array();
        }

        if (isset($options['use_server_array']) && $options['use_server_array'] == true) {
            $this->_use_server_array = true;
        }

        // Какой сайт?
        if (strlen($host)) {
            $this->_host = $host;
        } else {
            $this->_host = $_SERVER['HTTP_HOST'];
        }

        $this->_host = preg_replace('/^http:\/\//', '', $this->_host);
        $this->_host = preg_replace('/^www\./', '', $this->_host);

        // Какая страница?
        if (isset($options['request_uri']) && strlen($options['request_uri'])) {
            $this->_request_uri = $options['request_uri'];
        } elseif ($this->_use_server_array === false) {
            $this->_request_uri = getenv('REQUEST_URI');
        }

        if (strlen($this->_request_uri) == 0) {
            $this->_request_uri = $_SERVER['REQUEST_URI'];
        }

        // На случай, если хочется много сайтов в одной папке
        if (isset($options['multi_site']) && $options['multi_site'] == true) {
            $this->_multi_site = true;
        }

        // Выводить информацию о дебаге
        if (isset($options['debug']) && $options['debug'] == true) {
            $this->_debug = true;
        }

        // Определяем наш ли робот
        if (isset($_COOKIE['sape_cookie']) && ($_COOKIE['sape_cookie'] == _SAPE_USER)) {
            $this->_is_our_bot = true;
            if (isset($_COOKIE['sape_debug']) && ($_COOKIE['sape_debug'] == 1)) {
                $this->_debug = true;
                //для удобства дебега саппортом
                $this->_options            = $options;
                $this->_server_request_uri = $_SERVER['REQUEST_URI'];
                $this->_getenv_request_uri = getenv('REQUEST_URI');
                $this->_SAPE_USER          = _SAPE_USER;
            }
            if (isset($_COOKIE['sape_updatedb']) && ($_COOKIE['sape_updatedb'] == 1)) {
                $this->_force_update_db = true;
            }
        } else {
            $this->_is_our_bot = false;
        }

        // Сообщать об ошибках
        if (isset($options['verbose']) && $options['verbose'] == true || $this->_debug) {
            $this->_verbose = true;
        }

        // Кодировка
        if (isset($options['charset']) && strlen($options['charset'])) {
            $this->_charset = $options['charset'];
        } else {
            $this->_charset = 'windows-1251';
        }

        if (isset($options['fetch_remote_type']) && strlen($options['fetch_remote_type'])) {
            $this->_fetch_remote_type = $options['fetch_remote_type'];
        }

        if (isset($options['socket_timeout']) && is_numeric($options['socket_timeout']) && $options['socket_timeout'] > 0) {
            $this->_socket_timeout = $options['socket_timeout'];
        }

        // Всегда выводить чек-код
        if (isset($options['force_show_code']) && $options['force_show_code'] == true) {
            $this->_force_show_code = true;
        }

        if (!defined('_SAPE_USER')) {
            return $this->_raise_error('Не задана константа _SAPE_USER');
        }

        //Не обращаем внимания на регистр ссылок
        if (isset($options['ignore_case']) && $options['ignore_case'] == true) {
            $this->_ignore_case = true;
            $this->_request_uri = strtolower($this->_request_uri);
        }

        if (isset($options['show_counter_separately'])) {
            $this->_show_counter_separately = (bool)$options['show_counter_separately'];
        }

        if (isset($options['format']) && in_array($options['format'], array('serialize', 'php-require'))) {
            $this->_format = $options['format'];
        }

        if (isset($options['split_data_file'])) {
            $this->_split_data_file = (bool)$options['split_data_file'];
        }
    }

    /**
     * Получить строку User-Agent
     *
     * @return string
     */
    protected function _get_full_user_agent_string()
    {
        return $this->_user_agent . ' ' . $this->_version;
    }

    /**
     * Вывести дебаг-информацию
     *
     * @param $data
     *
     * @return string
     */
    protected function _debug_output($data)
    {
        $data = '<!-- <sape_debug_info>' . @base64_encode(serialize($data)) . '</sape_debug_info> -->';

        return $data;
    }

    /**
     * Функция для подключения к удалённому серверу
     */
    protected function _fetch_remote_file($host, $path, $specifyCharset = false)
    {

        $user_agent = $this->_get_full_user_agent_string();

        @ini_set('allow_url_fopen', 1);
        @ini_set('default_socket_timeout', $this->_socket_timeout);
        @ini_set('user_agent', $user_agent);
        if (
            $this->_fetch_remote_type == 'file_get_contents'
            ||
            (
                $this->_fetch_remote_type == ''
                &&
                function_exists('file_get_contents')
                &&
                ini_get('allow_url_fopen') == 1
            )
        ) {
            $this->_fetch_remote_type = 'file_get_contents';

            if ($specifyCharset && function_exists('stream_context_create')) {
                $opts    = array(
                    'http' => array(
                        'method' => 'GET',
                        'header' => 'Accept-Charset: ' . $this->_charset . "\r\n"
                    )
                );
                $context = @stream_context_create($opts);
                if ($data = @file_get_contents('http://' . $host . $path, null, $context)) {
                    return $data;
                }
            } else {
                if ($data = @file_get_contents('http://' . $host . $path)) {
                    return $data;
                }
            }
        } elseif (
            $this->_fetch_remote_type == 'curl'
            ||
            (
                $this->_fetch_remote_type == ''
                &&
                function_exists('curl_init')
            )
        ) {
            $this->_fetch_remote_type = 'curl';
            if ($ch = @curl_init()) {

                @curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
                @curl_setopt($ch, CURLOPT_HEADER, false);
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_socket_timeout);
                @curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
                if ($specifyCharset) {
                    @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset: ' . $this->_charset));
                }

                $data = @curl_exec($ch);
                @curl_close($ch);

                if ($data) {
                    return $data;
                }
            }
        } else {
            $this->_fetch_remote_type = 'socket';
            $buff                     = '';
            $fp                       = @fsockopen($host, 80, $errno, $errstr, $this->_socket_timeout);
            if ($fp) {
                @fputs($fp, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\n");
                if ($specifyCharset) {
                    @fputs($fp, "Accept-Charset: {$this->_charset}\r\n");
                }
                @fputs($fp, "User-Agent: {$user_agent}\r\n\r\n");
                while (!@feof($fp)) {
                    $buff .= @fgets($fp, 128);
                }
                @fclose($fp);

                $page = explode("\r\n\r\n", $buff);
                unset($page[0]);

                return implode("\r\n\r\n", $page);
            }
        }

        return $this->_raise_error('Не могу подключиться к серверу: ' . $host . $path . ', type: ' . $this->_fetch_remote_type);
    }

    /**
     * Функция чтения из локального файла
     */
    protected function _read($filename)
    {

        $fp = @fopen($filename, 'rb');
        @flock($fp, LOCK_SH);
        if ($fp) {
            clearstatcache();
            $length = @filesize($filename);

            if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                $mqr = @get_magic_quotes_runtime();
                @set_magic_quotes_runtime(0);
            }

            if ($length) {
                $data = @fread($fp, $length);
            } else {
                $data = '';
            }

            if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                @set_magic_quotes_runtime($mqr);
            }

            @flock($fp, LOCK_UN);
            @fclose($fp);

            return $data;
        }

        return $this->_raise_error('Не могу считать данные из файла: ' . $filename);
    }

    /**
     * Функция записи в локальный файл
     */
    protected function _write($filename, $data)
    {

        $fp = @fopen($filename, 'ab');
        if ($fp) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                ftruncate($fp, 0);

                if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                    $mqr = @get_magic_quotes_runtime();
                    @set_magic_quotes_runtime(0);
                }

                @fwrite($fp, $data);

                if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                    @set_magic_quotes_runtime($mqr);
                }

                @flock($fp, LOCK_UN);
                @fclose($fp);

                if (md5($this->_read($filename)) != md5($data)) {
                    @unlink($filename);

                    return $this->_raise_error('Нарушена целостность данных при записи в файл: ' . $filename);
                }
            } else {
                return false;
            }

            return true;
        }

        return $this->_raise_error('Не могу записать данные в файл: ' . $filename);
    }

    /**
     * Функция обработки ошибок
     */
    protected function _raise_error($e)
    {

        $this->_errors[] = $e;

        if ($this->_verbose == true) {
            print '<p style="color: red; font-weight: bold;">SAPE ERROR: ' . $e . '</p>';
        }

        return false;
    }

    /**
     * Получить имя файла с даными
     *
     * @return string
     */
    protected function _get_db_file()
    {
        return '';
    }

    /**
     * Получить имя файла с мета-информацией
     *
     * @return string
     */
    protected function _get_meta_file()
    {
        return '';
    }

    /**
     * Получить префикс файла в режиме split_data_file.
     *
     * @return string
     */
    protected function _get_save_filename_prefix()
    {
        if ($this->_split_data_file) {
            return '.' . crc32($this->_request_uri) % 100;
        } else {
            return '';
        }
    }
    /**
     * Получить URI к хосту диспенсера
     *
     * @return string
     */
    protected function _get_dispenser_path()
    {
        return '';
    }

    /**
     * Сохранить данные, полученные из файла, в объекте
     */
    protected function _set_data($data)
    {
    }

    /**
     * Расшифровывает данные
     *
     * @param string $data
     *
     * @return array|bool
     */
    protected function _uncode_data($data)
    {
        return @unserialize($data);
    }

    /**
     * Шифрует данные для сохранения.
     *
     * @param $data
     *
     * @return string
     */
    protected function _code_data($data)
    {
        return @serialize($data);
    }

    /**
     * Сохранение данных в файл.
     *
     * @param string $data
     * @param string $filename
     */
    protected function _save_data($data, $filename = '')
    {
        $this->_write($filename, $data);
    }
    /**
     * Загрузка данных
     */
    protected function _load_data()
    {
        $this->_db_file = $this->_get_db_file();

        if (!is_file($this->_db_file)) {
            // Пытаемся создать файл.
            if (@touch($this->_db_file)) {
                @chmod($this->_db_file, 0666); // Права доступа
            } else {
                return $this->_raise_error('Нет файла ' . $this->_db_file . '. Создать не удалось. Выставите права 777 на папку.');
            }
        }

        if (!is_writable($this->_db_file)) {
            return $this->_raise_error('Нет доступа на запись к файлу: ' . $this->_db_file . '! Выставите права 777 на папку.');
        }

        @clearstatcache();

        $data = $this->_read($this->_db_file);
        if (
            $this->_force_update_db
            || (
                !$this->_is_our_bot
                &&
                (
                    filemtime($this->_db_file) < (time() - $this->_cache_lifetime)
                    ||
                    filesize($this->_db_file) == 0
                    ||
                    $this->_uncode_data($data) == false
                )
            )
        ) {
            // Чтобы не повесить площадку клиента и чтобы не было одновременных запросов
            @touch($this->_db_file, (time() - $this->_cache_lifetime + $this->_cache_reloadtime));

            $path = $this->_get_dispenser_path();
            if (strlen($this->_charset)) {
                $path .= '&charset=' . $this->_charset;
            }
            if ($this->_format) {
                $path .= '&format=' . $this->_format;
            }
            foreach ($this->_server_list as $server) {
                if ($data = $this->_fetch_remote_file($server, $path)) {
                    if (substr($data, 0, 12) == 'FATAL ERROR:') {
                        $this->_raise_error($data);
                    } else {
                        // [псевдо]проверка целостности:
                        $hash = $this->_uncode_data($data);
                        if ($hash != false) {
                            // попытаемся записать кодировку в кеш
                            $hash['__sape_charset__']      = $this->_charset;
                            $hash['__last_update__']       = time();
                            $hash['__multi_site__']        = $this->_multi_site;
                            $hash['__fetch_remote_type__'] = $this->_fetch_remote_type;
                            $hash['__ignore_case__']       = $this->_ignore_case;
                            $hash['__php_version__']       = phpversion();
                            $hash['__server_software__']   = $_SERVER['SERVER_SOFTWARE'];

                            $data_new = $this->_code_data($hash);
                            if ($data_new) {
                                $data = $data_new;
                            }

                            $this->_save_data($data, $this->_db_file);
                            break;
                        }
                    }
                }
            }
        }

        // Убиваем PHPSESSID
        if (strlen(session_id())) {
            $session            = session_name() . '=' . session_id();
            $this->_request_uri = str_replace(array('?' . $session, '&' . $session), '', $this->_request_uri);
        }
        $data = $this->_uncode_data($data);
        if ($this->_split_data_file) {
            $meta = $this->_uncode_data($this->_read($this->_get_meta_file()));
            if (!is_array($data)) {
                $data = array();
            }
            if (is_array($meta)) {
                $data = array_merge($data, $meta);
            }
        }
        $this->_set_data($data);

        return true;
    }

    protected function _return_obligatory_page_content()
    {
        $s_globals = new SAPE_globals();

        $html = '';
        if (isset($this->_page_obligatory_output) && !empty($this->_page_obligatory_output)
            && false == $s_globals->page_obligatory_output_shown()
        ) {
            $s_globals->page_obligatory_output_shown(true);
            $html = $this->_page_obligatory_output;
        }

        return $html;
    }

    /**
     * Вернуть js-код
     * - работает только когда параметр конструктора show_counter_separately = true
     *
     * @return string
     */
    public function return_counter()
    {
        //если show_counter_separately = false и выполнен вызов этого метода,
        //то заблокировать вывод js-кода вместе с контентом
        if (false == $this->_show_counter_separately) {
            $this->_show_counter_separately = true;
        }

        return $this->_return_obligatory_page_content();
    }
}
