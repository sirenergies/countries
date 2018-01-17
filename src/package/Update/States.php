<?php

namespace PragmaRX\Countries\Package\Update;

use PragmaRX\Countries\Package\Support\Base;
use PragmaRX\Countries\Package\Support\Helper;

class States extends Base
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Updater
     */
    protected $updater;

    /**
     * @var Rinvex
     */
    private $rinvex;

    /**
     * Rinvex constructor.
     *
     * @param Helper $helper
     * @param Rinvex $rinvex
     * @param Updater $updater
     */
    public function __construct(Helper $helper, Rinvex $rinvex, Updater $updater)
    {
        $this->helper = $helper;

        $this->updater = $updater;

        $this->rinvex = $rinvex;
    }

    /**
     * Update states.
     */
    public function update()
    {
        $this->helper->progress('Updating states...');

        $this->helper->eraseDataDir($dataDir = '/states/default');

        $result = $this->helper->loadShapeFile('third-party/natural_earth/ne_10m_admin_1_states_provinces');

        $this->helper->message('Processing states...');

        $normalizerClosure = function ($item) {
            $item = $this->updater->addDataSource($item, 'natural');

            $item = $this->updater->addRecordType($item, 'state');

            return $this->updater->normalizeStateOrCityData($item);
        };

        $getCodeClosure = function ($item) {
            return $this->makeStatePostalCode($item);
        };

        $mergerClosure = function ($states) {
            return $this->rinvex->mergeCountryStatesWithRinvex($states);
        };

        list(, $states) = $this->updater->generateJsonFiles($result, $dataDir, $normalizerClosure, $getCodeClosure, $mergerClosure);

        $this->helper->progress('Generated '.count($states).' states.');
    }

    /**
     * Get the state postal code.
     *
     * @param $item
     * @return mixed
     */
    public function makeStatePostalCode($item)
    {
        $item = countriesCollect($item);

        if ($item->iso_3166_2 !== '') {
            $code = explode('-', $item->iso_3166_2);

            if (count($code) > 1) {
                return $code[1];
            }
        }

        if (! empty(trim($item->postal))) {
            $item->postal;
        }

        if ($item->code_hasc !== '') {
            $code = explode('.', $item->code_hasc);

            if (count($code) > 1) {
                return $code[1];
            }
        }

        return $this->helper->caseForKey($item->iso_3166_2);
    }
}