<?php
/*
=============================================================================
BASE - базовый класс для модулей
=============================================================================
Автор:   ПафНутиЙ
URL:     http://pafnuty.name/
twitter: https://twitter.com/pafnuty_name
google+: http://gplus.to/pafnuty
email:   pafnuty10@gmail.com
=============================================================================
*/

if (!defined('DATALIFEENGINE')) {
	die("Go fuck yourself!");
}

define('BASE_DIR', ENGINE_DIR . '/modules/base');

require_once(BASE_DIR . '/core/Fenom.php');
\Fenom::registerAutoload(BASE_DIR . '/core/');

require_once(ENGINE_DIR . '/modules/functions.php');

/**
 * BaseClass
 */
class base {

	public $result;

	public $dle_config = array();
	public $cfg = array();
	public $tplOptions = array();
	public $tpl;
	public $db;
	const ROOT_DIR = ROOT_DIR;
	const ENGINE_DIR = ENGINE_DIR;
	const BASE_DIR = BASE_DIR;

	function __construct() {

		// Подключаем конфиг DLE
		$this->dle_config = $this->getDleConfig();

		// Подключаем класс для работы с БД
		$this->db = $this->getDb();

	}

	public function getDb() {
		return SafeMySQL::getInstanse(array(
			'host'    => DBHOST,
			'user'    => DBUSER,
			'pass'    => DBPASS,
			'db'      => DBNAME,
			'charset' => COLLATE,
		));
	}

	public function getTemplater($tplOptions) {
		$this->tpl = Fenom::factory(
			ROOT_DIR . '/templates/' . $this->dle_config['skin'] . '/',
			ENGINE_DIR . '/cache/',
			$tplOptions
		);
		// Добавляем модификаторы
		$this->addModifiers();
	}

	public function setConfig($cfg = array()) {
		return $cfg;
	}

	public static function getDleConfig() {
		include(ENGINE_DIR . '/data/config.php');
		return $config;
	}

	public function addModifiers() {

		// Добавляем свой модификатор в шаблонизатор для ограничения кол-ва символов в тексте
		$config = $this->dle_config;

		$this->tpl->addModifier(
			'limit', function ($data, $limit, $etc = '&hellip;', $wordcut = false) use ($config) {
				return bpModifiers::textLimit($data, $limit, $etc, $wordcut, $config['charset']);
			}
		);

		// Добавляем свой модификатор в шаблонизатор для вывода картинок
		$this->tpl->addModifier(
			'image', function ($data, $noimage = '', $imageType = 'small', $number = 1, $size, $quality, $resizeType = 'auto', $grabRemote = true, $showSmall = false, $subdir = false) use ($config) {
				return bpModifiers::getImage($data, $noimage, $imageType, $number, $size, $quality, $resizeType, $grabRemote, $showSmall, $subdir, $config);
			}
		);

		// Добавляем свой модификатор в шаблонизатор для вывода картинок
		$this->tpl->addModifier(
			'declination', function ($n, $word) {
				return bpModifiers::declinationWords($n, $word);
			}
		);

		// Добавляем свой модификатор в шаблонизатор для вывода форматированной даты
		$this->tpl->addModifier(
			'dateformat', function ($data, $_f = false) {
				return formateDate($data, $_f);
			}
		);

		// Добавляем свой модификатор в шаблонизатор для вывода форматированной даты
		$this->tpl->addModifier(
			'catinfo', function ($data, $info = false, $noicon = false) {
				return getCatInfo($data, $info, $noicon);
			}
		);
	}



	/**
	 * @param $data - массив с информацией о статье
	 *
	 * @return string URL для категории
	 */

	public function getPostUrl($data) {
		global $cat_info;

		if ($this->dle_config['allow_alt_url'] && $this->dle_config['allow_alt_url'] != 'no') {
			if (
				($this->dle_config['version_id'] < 9.6 && $this->dle_config['seo_type'])
				||
				($this->dle_config['version_id'] >= 9.6 && ($this->dle_config['seo_type'] == 1 || $this->dle_config['seo_type'] == 2))
			) {
				if (intval($data['category']) && $this->dle_config['seo_type'] == 2) {
					$url = $this->dle_config['http_home_url'] . get_url(intval($data['category'])) . '/' . $data['id'] . '-' . $data['alt_name'] . '.html';
				} else {
					$url = $this->dle_config['http_home_url'] . $data['id'] . '-' . $data['alt_name'] . '.html';
				}
			} else {
				$url = $this->dle_config['http_home_url'] . date('Y/m/d/', $data['date']) . $data['alt_name'] . '.html';
			}
		} else {
			$url = $this->dle_config['http_home_url'] . 'index.php?newsid=' . $data['id'];
		}

		return $url;
	}

	/**
	 * Получение диапазона между двумя цифрами, и не только
	 *
	 * @param bool $diapazone
	 * @param bool $subcats
	 *
	 * @internal param string $diapasone
	 * @return string
	 * @author   Elkhan I. Isaev <elhan.isaev@gmail.com>
	 */

	public function getDiapazone($diapazone = false, $subcats = false) {
		if ($diapazone !== false) {
			$diapazone = str_replace(" ", "", $diapazone);

			if (strpos($diapazone, ',') !== false) {
				$diapazoneArray = explode(',', $diapazone);
				$diapazoneArray = array_diff($diapazoneArray, array(NULL));

				foreach ($diapazoneArray as $v) {
					if (strpos($v, '-') !== false) {
						preg_match("#(\d+)-(\d+)#i", $v, $test);

						$diapazone = !empty($diapazone) && is_array($diapazone) ?
							array_merge($diapazone, (!empty ($test) ? range($test[1], $test[2]) : array()))
							: (!empty ($test) ? range($test[1], $test[2]) : array());

					} else {
						$diapazone = !empty($diapazone) && is_array($diapazone) ?
							array_merge($diapazone, (!empty ($v) ? array((int)$v) : array()))
							: (!empty ($v) ? array((int)$v) : array());
					}
				}

			} elseif (strpos($diapazone, '-') !== false) {

				preg_match("#(\d+)-(\d+)#i", $diapazone, $test);
				$diapazone = !empty ($test) ? range($test[1], $test[2]) : array();

			} else {
				$diapazone = array((int)$diapazone);
			}
			if (!empty($diapazone)) {
				if ($subcats && function_exists('get_sub_cats')) {
					foreach ($diapazone as $d) {
						$_sc = explode('|', get_sub_cats($d));
						foreach ($_sc as $v) {
							array_push($diapazone, $v);
						}
					}
				}
				$diapazone = array_unique($diapazone);
			} else {
				$diapazone = array();
			}

			$diapazone = implode(',', $diapazone);
		}

		return $diapazone;

	}

	/**
	 * Формируем ссылки с тегами
	 *
	 * @param  string $tags строка с тегами
	 *
	 * @return string       строка с сылками
	 */

	public function tagsLink($tags) {
		$showTags = '';
		if ($this->dle_config['allow_tags'] && $tags) {
			$showTagsArr = array();
			$tags        = explode(",", $tags);

			foreach ($tags as $value) {
				$value = trim($value);
				if ($this->dle_config['allow_alt_url'] && $this->dle_config['allow_alt_url'] != 'no') {
					$showTagsArr[] = "<a href=\"" . $this->dle_config['http_home_url'] . "tags/" . urlencode($value) . "/\">" . $value . "</a>";
				} else {
					$showTagsArr[] = "<a href=\"" . $this->dle_config['http_home_url'] . "?do=tags&amp;tag=" . urlencode($value) . "\">" . $value . "</a>";
				}

				$showTags = implode(', ', $showTagsArr);
			}
		}

		return $showTags;
	}


} // base Class



/**
 * Форматируем дату
 *
 * @param  string $date дата
 * @param bool $_f
 *
 * @internal param bool $_f если false - форматирует в соответствии с настройками движка
 *
 * @return string        отформатированная дата
 */
function formateDate($date, $_f = false) {

	global $lang, $config, $langdate;

	if (!$lang['charset']) {
		@include_once ROOT_DIR . '/language/' . $config['langs'] . '/website.lng';
	}

	$date = strtotime($date);

	if (!$_f) {

		if (date('Ymd', $date) == date('Ymd')) {
			$showDate = $lang['time_heute'] . langdate(', H:i', $date);
		} elseif (date('Ymd', $date) == date('Ymd') - 1) {
			$showDate = $lang['time_gestern'] . langdate(', H:i', $date);
		} else {
			$showDate = langdate($config['timestamp_active'], $date);
		}
	} else {
		$showDate = langdate($_f, $date);
	}

	return $showDate;
}

/**
 * @param      $category
 * @param bool $info
 * @param bool $noicon
 *
 * @return array|string
 */
function getCatInfo($category, $info = false, $noicon = false) {
	global $cat_info, $config;

	$my_cat      = array();
	$my_cat_icon = array();
	$my_cat_link = array();
	$cat_return  = array();

	$separator = ($config['category_separator']) ? $config['category_separator'] : ', ';


	$cat_list = explode(',', $category);

	foreach ($cat_list as $cat) {
		if (isset($cat_info[$cat])) {

			$my_cat[] = $cat_info[$cat]['name'];
			if ($cat_info[$cat]['icon']) {
				$my_cat_icon[] = '<img src="' . $cat_info[$cat]['icon'] . '" alt="' . $cat_info[$cat]['name'] . '" />';
			} else {
				$my_cat_icon[] = (!$noicon) ? false : '<img src="' . $noicon . '" alt="' . $cat_info[$cat]['name'] . '" />';
			}
			if ($config['allow_alt_url'] && $config['allow_alt_url'] != 'no') {
				$my_cat_link[] = '<a href="' . $config['http_home_url'] . get_url($cat_info[$cat]['id']) . '/">' . $cat_info[$cat]['name'] . '</a>';
			} else {
				$my_cat_link[] = '<a href="' . $config['http_home_url'] . '?do=cat&category=' . $cat_info[$cat]['alt_name'] . '">' . $cat_info[$cat]['name'] . '</a>';
			}
		}
	}

	$cat_return['name'] = implode($separator, $my_cat);
	$cat_return['icon'] = implode(' ', $my_cat_icon);
	$cat_return['link'] = implode($separator, $my_cat_link);
	$cat_return['url']  = ($category) ? $config['http_home_url'] . get_url(intval($category)) . '/' : '/';


	switch ($info) {
		case 'name':
			return $cat_return['name'];
			break;

		case 'icon':
			return $cat_return['icon'];
			break;

		case 'link':
			return $cat_return['link'];
			break;

		case 'url':
			return $cat_return['url'];
			break;

		default:
			return $cat_return;
			break;
	}

}

/**
 * @return mixed
 */
function getNormalHomeHttp() {
	$config['http_home_url'] = str_replace('http://' . $_SERVER['HTTP_HOST'], '', $config['http_home_url']);

	return $config['http_home_url'];
}


/**
 * Показ рейтинга через модуль. Функция переписана из стандартной т.к. стандартная имела ID, конликтующие с самими собой.
 * @param  integer  $id       ID новости
 * @param  integer  $rating   значение рейтинга
 * @param  integer  $vote_num кол-во голосов
 * @param  boolean  $allow    доступность рейтинга для юзера
 * @return mixed
 */
function baseShowRating($id, $rating, $vote_num, $allow = true) {
	global $lang;

	if( $rating AND $vote_num ) $rating = round( ($rating / $vote_num), 0 );
	else $rating = 0;
	$rating = $rating * 20;

	if( !$allow ) {

		$rated = <<<HTML
<div class="rating">
	<ul class="unit-rating">
		<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
	</ul>
</div>
HTML;

		return $rated;
	}

	$rated = <<<HTML
<div data-rating-layer="{$id}">
	<div class="rating">
		<ul class="unit-rating">
			<li class="current-rating" style="width:{$rating}%;">{$rating}</li>
			<li><a href="#" title="{$lang['useless']}" class="r1-unit" onclick="base_rate('1', '{$id}'); return false;">1</a></li>
			<li><a href="#" title="{$lang['poor']}" class="r2-unit" onclick="base_rate('2', '{$id}'); return false;">2</a></li>
			<li><a href="#" title="{$lang['fair']}" class="r3-unit" onclick="base_rate('3', '{$id}'); return false;">3</a></li>
			<li><a href="#" title="{$lang['good']}" class="r4-unit" onclick="base_rate('4', '{$id}'); return false;">4</a></li>
			<li><a href="#" title="{$lang['excellent']}" class="r5-unit" onclick="base_rate('5', '{$id}'); return false;">5</a></li>
		</ul>
	</div>
</div>
HTML;

	return $rated;
}


?>