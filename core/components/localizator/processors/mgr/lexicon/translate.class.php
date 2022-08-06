<?php

class localizatorLexiconTranslateProcessor extends modProcessor
{

	public function process()
	{
		$this->localizator = $this->modx->getService('localizator');

		if (!$default_language = $this->modx->getOption('localizator_default_language')) {
			return $this->failure($this->modx->lexicon('localizator_item_err_default_language'));
		}

		$tranlate_all = $this->modx->getOption('localizator_translate_translated_fields');

		$languages = [];
		$processed = 0;

		$_languages = $this->modx->getIterator('localizatorLanguage');
		foreach ($_languages as $language) {
			$key = $language->cultureKey ?: $language->key;
			if ($key != $default_language) {
				$languages[] = $key;
			}
		}

		$c = $this->modx->newQuery('modLexiconEntry');
		$c->limit(1000000);
		$c->where(array(
			'namespace' => 'localizator',
			'topic' => 'site',
			'language' => $default_language
		));

		$total = $this->modx->getCount('modLexiconEntry', $c);
		$entries = $this->modx->getIterator('modLexiconEntry', $c);
		foreach ($entries as $entry) {

			foreach ($languages as $language) {
				$tmp = $this->modx->getObject('modLexiconEntry', array(
					'namespace' => 'localizator',
					'topic' => 'site',
					'language' => $language,
					'name' => $entry->name,
				));

				// если уже есть запись и указано не перезаписывать - прерываем цикл
				if ($tmp && !$tranlate_all) {
					break;
				}

				if (!$tmp) {
					$tmp = $this->modx->newObject('modLexiconEntry');
					$tmp->fromArray(array(
						'namespace' => 'localizator',
						'topic' => 'site',
						'language' => $language,
						'name' => $entry->name,
					));
				}

				$translation = $this->localizator->translate($entry->value, $default_language, $language);
				if (!$translation) continue;

				$tmp->set('value', $translation);
				$tmp->save();
			}

			$processed++;
		}

		return $this->success('', array(
			'total' => $total,
			'processed' => $processed,
		));
	}
}

return 'localizatorLexiconTranslateProcessor';
