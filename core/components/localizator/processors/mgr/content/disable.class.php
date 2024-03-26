<?php

require_once(dirname(__FILE__) . '/update.class.php');

class localizatorContentDisableProcessor extends localizatorContentUpdateProcessor
{
    /**
     * @return bool
     */
    public function beforeSet()
    {
        $this->properties = array(
            'active' => false,
        );

        return true;
    }
}

return 'localizatorContentDisableProcessor';
