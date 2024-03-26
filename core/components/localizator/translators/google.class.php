<?php

class Google
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
			'key' => $this->modx->getOption('localizator_key_google')
		), $config);
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

		if (!$this->config['key']) {
			return $this->modx->error->failure($this->modx->lexicon('localizator_item_err_google_key'));
		}

		if (!$text) return;
		$output = '';
		$data = array(
			'key' 		=> $this->config['key'],
			'source' 	=> $from,
			'target' 	=> $to,
			'q' 		=> $text,
			'format' 	=> 'html',
		);

		$ch = curl_init('https://www.googleapis.com/language/translate/v2');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response, true);
		if ($response['code'] == 200) {
			$output = $response['data']['translations'][0]['translatedText'];
		} else {
			$this->modx->log(1, 'localizator: google translate error - ' . $response['error']['errors'][0]['message']);
		}

		return $output;
	}
}
