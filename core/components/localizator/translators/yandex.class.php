<?php

class Yandex
{

	/** @var modX $modx */
	public $modx;


	/**
	 * @param modX $modx
	 * @param array $config
	 */
	public function __construct(modX $modx, array $config = [])
	{
		$this->modx = $modx;

		$this->config = array_merge(array(
			'key' => $this->modx->getOption('localizator_key_yandex')
		), $config);

		if (!$this->config['key']) {
			$this->modx->log(1, 'localizator: yandex error - yandex api key not found in system setting');
		}
	}


	/**
	 * @param string $text
	 * @param string $from
	 * @param string $to
	 *
	 * @return string
	 */
	public function translate($text, $from, $to)
	{
		if (!$text) return;
		$output = '';
		$data = array(
			'key' => $this->config['key'],
			'lang' => $from . '-' . $to,
			'format' => 'html',
		);

		// doc  
		// https://tech.yandex.ru/translate/doc/dg/concepts/About-docpage/
		$text = $this->prepare_text($text);
		foreach ($text as $part) {
			$data['text'] = $part;
			$ch = curl_init('https://translate.yandex.net/api/v1.5/tr.json/translate');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			$response = json_decode($response, true);
			if ($response['code'] == 200) {
				$output .= implode('', $response['text']);
			} else {
				$this->modx->log(1, 'localizator: yandex error - ' . $response['code'] . ', see https://tech.yandex.ru/translate/doc/dg/reference/translate-docpage/');
			}
		}

		return $output;
	}


	/**
	 * @param string $text
	 * @param int $limit 
	 *
	 * @return array
	 */
	public function prepare_text($text, $limit = 2000)
	{
		if ($limit > 0) {
			$ret = array();
			$limiten = mb_strlen($text, "UTF-8");
			for ($i = 0; $i < $limiten; $i += $limit) {
				$ret[] = mb_substr($text, $i, $limit, "UTF-8");
			}
			return $ret;
		}
		return preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY);
	}
}
