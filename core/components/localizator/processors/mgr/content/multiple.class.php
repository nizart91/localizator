<?php

class localizatorContentMultipleProcessor extends modProcessor
{

    /**
     * @return array|string
     */
    public function process()
    {
        if (!$method = $this->getProperty('method', false)) {
            return $this->failure();
        }
        $ids = json_decode($this->getProperty('ids'), true);
        if (empty($ids)) {
            return $this->success();
        }

        /** @var localizator $localizator */
        $localizator = $this->modx->getService('localizator');

        foreach ($ids as $id) {
            /** @var modProcessorResponse $response */
            $response = $this->modx->runProcessor(
                'mgr/content/' . $method,
                array(
                    'id' => $id
                ),
                array(
                    'processors_path' => $localizator->config['processorsPath']
                )
            );
            if ($response->isError()) {
                return $response->getResponse();
            }
        }

        return $this->success();
    }
}

return 'localizatorContentMultipleProcessor';
