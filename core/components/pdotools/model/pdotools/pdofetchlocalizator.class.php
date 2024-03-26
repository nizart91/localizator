<?php

if (!class_exists('pdoFetch')) {
    require_once dirname(__FILE__) . '/pdofetch.class.php';
}

class pdoFetchLocalizator extends pdoFetch
{
    public function addTVs()
    {
        parent::addTVs();

        if ($this->config['tvsJoin']) {
            $this->config['localizator_key'] = $this->modx->getOption('localizator_key', $this->config, $this->modx->getOption('localizator_key', null), true);
            $this->config['localizatorTVs'] = array();
            $q = $this->modx->newQuery('modTemplateVar')
                ->where(array(
                    'name:IN' => array_keys($this->config['tvsJoin']),
                    'localizator_enabled' => 1,
                ))
                ->select('name');
            if ($q->prepare() && $q->stmt->execute()) {
                $this->config['localizatorTVs'] = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            foreach ($this->config['localizatorTVs'] as $name) {
                $name = strtolower($name);
                $alias = 'TV' . $name;
                if (isset($this->config['tvsJoin'][$name])) {
                    $this->config['tvsJoin'][$name]['class'] = 'locTemplateVarResource';
                    $this->config['tvsJoin'][$name]['on'] .= " AND `{$alias}`.`key` = " . $this->modx->quote($this->config['localizator_key']);
                }
            }
        }
    }
    /**
     * Prepares fetched rows and process template variables
     *
     * @param array $rows
     *
     * @return array
     */
    public function prepareRows(array $rows = array())
    {
        $time = microtime(true);

        if (
            isset($this->config['localizatorTVs']) && !empty($this->config['localizatorTVs']) &&
            (!empty($this->config['includeTVs']) && (!empty($this->config['prepareTVs']) || !empty($this->config['processTVs'])))
        ) {
            $tvs = array_map('trim', explode(',', $this->config['includeTVs']));
            $prepare = ($this->config['prepareTVs'] == 1)
                ? $tvs
                : array_map('trim', explode(',', $this->config['prepareTVs']));
            $process = ($this->config['processTVs'] == 1)
                ? $tvs
                : array_map('trim', explode(',', $this->config['processTVs']));

            $processloctvs = array_intersect($process, $this->config['localizatorTVs']);
            $this->config['processTVs'] = implode(',', array_diff($process, $processloctvs));
            $this->config['prepareTVs'] = implode(',', array_diff($prepare, $processloctvs));

            if (!empty($processloctvs)) {
                foreach ($rows as &$row) {
                    // Extract JSON fields
                    if ($this->config['decodeJSON']) {
                        foreach ($row as $k => $v) {
                            if (!empty($v) && is_string($v) && ($v[0] == '[' || $v[0] == '{')) {
                                $tmp = json_decode($v, true);
                                if (json_last_error() == JSON_ERROR_NONE) {
                                    $row[$k] = $tmp;
                                }
                            }
                        }
                    }

                    foreach ($processloctvs as $tv) {

                        /** @var modTemplateVar $templateVar */
                        if (!$templateVar = $this->getStore($tv, 'tv')) {
                            if ($templateVar = $this->modx->getObject('modTemplateVar', array('name' => $tv))) {
                                $sourceCache = isset($prepareTypes[$templateVar->type])
                                    ? $templateVar->getSourceCache($this->modx->context->get('key'))
                                    : null;
                                $templateVar->set('sourceCache', $sourceCache);
                                $this->setStore($tv, $templateVar, 'tv');
                            } else {
                                $this->addTime('Could not process or prepare TV "' . $tv . '"');
                                continue;
                            }
                        }

                        $tvPrefix = !empty($this->config['tvPrefix']) ?
                            trim($this->config['tvPrefix'])
                            : '';
                        $key = $tvPrefix . $templateVar->name;

                        $row[$key] = localizatorContent::renderTVOutput($this->modx, $templateVar, $row[$key], $row['id']);
                    }
                }
                $this->addTime('Processed Localizator TVs: ' . implode(', ', $processloctvs), microtime(true) - $time);
            }
        }

        return parent::prepareRows($rows);
    }
}
