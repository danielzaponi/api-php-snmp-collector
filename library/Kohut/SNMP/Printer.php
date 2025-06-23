<?php

/**
 * @see Kohut_SNMP_Abstract
 */
require_once 'Kohut/SNMP/Abstract.php';

/**
 * Class for getting information about printer by SNMP protocol
 * This class has dependency on PHP extension "php_snmp.dll"
 *
 * @version    v0.11    2011-08-31
 * @author     Petr Kohut <me@petrkohut.cz>    -    http://www.petrkohut.cz
 * @category   Kohut
 * @package    Kohut_SNMP
 * @copyright  Copyright (c) 2011 - Petr Kohut
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Kohut_SNMP_Printer extends Kohut_SNMP_Abstract
{

    /**
     * Printer types
     */
    const PRINTER_TYPE_MONO  = 'mono printer';
    const PRINTER_TYPE_COLOR = 'color printer';

    /**
     * Printer colors
     */
    const CARTRIDGE_COLOR_CYAN    = 'cyan';
    const CARTRIDGE_COLOR_MAGENTA = 'magenta';
    const CARTRIDGE_COLOR_YELLOW  = 'yellow';
    const CARTRIDGE_COLOR_BLACK   = 'black';

    /**
     * SNMP MARKER_SUPPLIES possible results
     */
    const MARKER_SUPPLIES_UNAVAILABLE    = -1;
    const MARKER_SUPPLIES_UNKNOWN        = -2;
    const MARKER_SUPPLIES_SOME_REMAINING = -3; // means that there is some remaining but unknown how much

    /**
     * SNMP printer object ids
     */
    const SNMP_PRINTER_FACTORY_ID                     = '.1.3.6.1.2.1.25.3.2.1.3.1'; //1.3.6.1.2.1.1.1.0
    const SNMP_PRINTER_RUNNING_TIME                   = '.1.3.6.1.2.1.1.3.0';   // TODO: Create function to handle this
    const SNMP_PRINTER_SERIAL_NUMBER                  = '.1.3.6.1.2.1.43.5.1.1.17.1';
    const SNMP_PRINTER_VENDOR_NAME                    = '.1.3.6.1.2.1.43.9.2.1.8.1.1';
    const SNMP_NUMBER_OF_PRINTED_PAPERS               = '.1.3.6.1.2.1.43.10.2.1.4.1.1';

    const SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOTS     = '.1.3.6.1.2.1.43.11.1.1.8.1';
    const SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_1    = '.1.3.6.1.2.1.43.11.1.1.8.1.1';
    const SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_2    = '.1.3.6.1.2.1.43.11.1.1.8.1.2';
    const SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_3    = '.1.3.6.1.2.1.43.11.1.1.8.1.3';
    const SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_4    = '.1.3.6.1.2.1.43.11.1.1.8.1.4';
    const SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_5    = '.1.3.6.1.2.1.43.11.1.1.8.1.5';

    const SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOTS  = '.1.3.6.1.2.1.43.11.1.1.9.1';
    const SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_1 = '.1.3.6.1.2.1.43.11.1.1.9.1.1';
    const SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_2 = '.1.3.6.1.2.1.43.11.1.1.9.1.2';
    const SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_3 = '.1.3.6.1.2.1.43.11.1.1.9.1.3';
    const SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_4 = '.1.3.6.1.2.1.43.11.1.1.9.1.4';
    const SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_5 = '.1.3.6.1.2.1.43.11.1.1.9.1.5';

    const SNMP_SUB_UNIT_TYPE_SLOTS                    = '.1.3.6.1.2.1.43.11.1.1.6.1';
    const SNMP_SUB_UNIT_TYPE_SLOT_1                   = '.1.3.6.1.2.1.43.11.1.1.6.1.1';
    const SNMP_SUB_UNIT_TYPE_SLOT_2                   = '.1.3.6.1.2.1.43.11.1.1.6.1.2';
    const SNMP_SUB_UNIT_TYPE_SLOT_3                   = '.1.3.6.1.2.1.43.11.1.1.6.1.3';
    const SNMP_SUB_UNIT_TYPE_SLOT_4                   = '.1.3.6.1.2.1.43.11.1.1.6.1.4';

    const SNMP_CARTRIDGE_COLOR_SLOT_1                 = '.1.3.6.1.2.1.43.12.1.1.4.1.1';
    const SNMP_CARTRIDGE_COLOR_SLOT_2                 = '.1.3.6.1.2.1.43.12.1.1.4.1.2';
    const SNMP_CARTRIDGE_COLOR_SLOT_3                 = '.1.3.6.1.2.1.43.12.1.1.4.1.3';
    const SNMP_CARTRIDGE_COLOR_SLOT_4                 = '.1.3.6.1.2.1.43.12.1.1.4.1.4';

    /**
     * Detecta, sanitiza e converte uma string hexadecimal de forma segura.
     *
     * @param string $input
     * @return string Retorna a string convertida ou original, se não for hexadecimal
     */
    private function safeHexDecode($input)
    {
        // Verifica se começa com "Hex-" ou contém muitos espaços/hexadecimais
        $looksLikeHex = stripos($input, 'Hex-') === 0 || preg_match('/([A-Fa-f0-9]{2} ?){4,}/', $input);

        if (!$looksLikeHex) {
            return trim($input); // Já é uma string legível
        }

        // Remove prefixo Hex- e caracteres não-hex
        $clean = preg_replace('/[^a-fA-F0-9]/', '', preg_replace('/^Hex-/', '', $input));

        // Corrige se tiver número ímpar de caracteres
        if (strlen($clean) % 2 !== 0) {
            $clean = '0' . $clean;
        }

        $decoded = hex2bin($clean);

        // Remove bytes de controle invisíveis
        return $decoded !== false ? trim($decoded, "\x00..\x1F") : trim($input);
    }

    /**
     * Verifica se uma string tem características de dado hexadecimal.
     *
     * @param string $input
     * @return bool
     */
    private function isHexadecimal($input)
    {
        $trimmed = trim($input);

        // Ignora strings muito curtas
        if (strlen($trimmed) < 4) {
            return false;
        }

        // Detecta prefixo "Hex-" ou muitos pares hexadecimais
        return stripos($trimmed, 'Hex-') === 0 ||
            preg_match('/^([a-fA-F0-9]{2}[\s\r\n]*){3,}$/', $trimmed);
    }

    /**
     * Converte uma string hexadecimal limpa em ASCII, removendo bytes invisíveis.
     *
     * @param string $input
     * @return string
     */
    private function sanitizeHex($input)
    {
        // Remove prefixo e caracteres não-hexadecimais
        $clean = preg_replace('/[^a-fA-F0-9]/', '', preg_replace('/^Hex-/', '', $input));

        // Corrige se tiver número ímpar de caracteres
        if (strlen($clean) % 2 !== 0) {
            $clean = '0' . $clean;
        }

        $decoded = hex2bin($clean);

        // Se falhar, retorna original
        if ($decoded === false) {
            return trim($input);
        }

        // Remove caracteres de controle (ex: \x00, \x0e, etc.)
        return trim($decoded, "\x00..\x1F");
    }





    /**
     * Function gets and return what type of printer we are working with,
     * or returns false if error occurred
     *
     * @return string Type of printer (PRINTER_TYPE_MONO|PRINTER_TYPE_COLOR)
     */
    public function getTypeOfPrinter()
    {
        $colorCartridgeSlot1 = $this->getSNMPString(self::SNMP_CARTRIDGE_COLOR_SLOT_1);
        if ($colorCartridgeSlot1 !== false) {

            if (strtolower($colorCartridgeSlot1) === self::CARTRIDGE_COLOR_CYAN) {

                /**
                 * We found CYAN color catridge in slot1 so it is color printer
                 */
                return self::PRINTER_TYPE_COLOR;
            } else {

                /**
                 * else it is mono printer
                 */
                return self::PRINTER_TYPE_MONO;
            }
        }

        return false;
    }

    /**
     * Function returns true if it is color printer
     *
     * @return boolean
     */
    public function isColorPrinter()
    {
        $type = $this->getTypeOfPrinter();
        if ($type !== false) {
            return ($type === self::PRINTER_TYPE_COLOR) ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Function returns true if it is color printer
     *
     * @return boolean
     */
    public function isMonoPrinter()
    {
        $type = $this->getTypeOfPrinter();
        if ($type !== false) {
            return ($type === self::PRINTER_TYPE_MONO) ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Function gets factory id (name) of the printer,
     * or returns false if call failed
     *
     * @return string|boolean
     */
    public function getFactoryId()
    {
        return $this->getSNMPString(self::SNMP_PRINTER_FACTORY_ID);
    }

    /**
     * Function gets vendor name of printer
     *
     * @return string|boolean
     */
    public function getVendorName()
    {
        return $this->getSNMPString(self::SNMP_PRINTER_VENDOR_NAME);
    }

    /**
     * Function gets serial number of printer
     *
     * @return string|boolean
     */
    public function getSerialNumber()
    {
        return $this->getSNMPString(self::SNMP_PRINTER_SERIAL_NUMBER);
    }

    /**
     * Function gets a count of printed papers,
     * or returns false if call failed
     *
     * @return int|boolean
     */
    public function getNumberOfPrintedPapers()
    {
        snmp_set_quick_print(true);
        $numberOfPrintedPapers = $this->get(self::SNMP_NUMBER_OF_PRINTED_PAPERS);
        snmp_set_quick_print(false);

        return ($numberOfPrintedPapers !== false) ? (int) $numberOfPrintedPapers : false;
    }

    /**
     * Function gets description about black catridge of the printer,
     * or returns false if call failed
     *
     * @return string|boolean
     */
    public function getBlackCatridgeType()
    {
        if ($this->isColorPrinter()) {
            return $this->getSNMPString(self::SNMP_SUB_UNIT_TYPE_SLOT_4);
        } elseif ($this->isMonoPrinter()) {
            $raw = $this->getSNMPString(self::SNMP_SUB_UNIT_TYPE_SLOT_1);

            if ($this->isHexadecimal($raw)) {
                return $this->sanitizeHex($raw);
            }

            return trim($raw); // retorno direto se não for hexadecimal

        } else {
            return false;
        }
    }



    /**
     * Function gets description about cyan catridge of the printer,
     * or returns false if call failed
     *
     * @return string|boolean
     */
    public function getCyanCatridgeType()
    {
        if ($this->isColorPrinter() === true) {
            return $this->getSNMPString(self::SNMP_SUB_UNIT_TYPE_SLOT_1);
        } else {
            return false;
        }
    }

    /**
     * Function gets description about magenta catridge of the printer,
     * or returns false if call failed
     *
     * @return string|boolean
     */
    public function getMagentaCatridgeType()
    {
        if ($this->isColorPrinter() === true) {
            return $this->getSNMPString(self::SNMP_SUB_UNIT_TYPE_SLOT_2);
        } else {
            return false;
        }
    }

    /**
     * Function gets description about yellow catridge of the printer,
     * or returns false if call failed
     *
     * @return string|boolean
     */
    public function getYellowCatridgeType()
    {
        if ($this->isColorPrinter() === true) {
            return $this->getSNMPString(self::SNMP_SUB_UNIT_TYPE_SLOT_3);
        } else {
            return false;
        }
    }

    /**
     * Function gets sub-unit percentage level of the printer,
     * or
     * -1 : MARKER_SUPPLIES_UNAVAILABLE Level is unavailable
     * -2 : MARKER_SUPPLIES_UNKNOWN Level is unknown
     * -3 : MARKER_SUPPLIES_SOME_REMAINING Information about level is only that there is some remaining, but we don't know how much
     *
     * or returns false if call failed
     *
     * @param string $maxValueSNMPSlot SNMP object id
     * @param string $actualValueSNMPSlot SNMP object id
     * @return int|float|boolean
     */
    protected function getSubUnitPercentageLevel($maxValueSNMPSlot, $actualValueSNMPSlot)
    {
        $max = str_replace("INTEGER: ", "", $this->get($maxValueSNMPSlot));
        $actual = str_replace("INTEGER: ", "", $this->get($actualValueSNMPSlot));

        if ($max === false || $actual === false) {
            return false;
        }

        if ((int) $actual <= 0) {

            /**
             * Actual level of drum is unavailable, unknown or some unknown remaining
             */
            return (int) $actual;
        } else {

            /**
             * Counting result in percent format
             */
            return ($actual / ($max / 100));
        }
    }

    /**
     * Function gets actual level of black toner (in percents)
     * or returns false if call failed
     *
     * @see getSubUnitPercentageLevel
     * @return int|float|boolean
     */
    public function getBlackTonerLevel()
    {
        if ($this->isColorPrinter()) {
            return $this->getSubUnitPercentageLevel(
                self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_4,
                self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_4
            );
        } elseif ($this->isMonoPrinter()) {
            return $this->getSubUnitPercentageLevel(
                self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_1,
                self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_1
            );
        } else {
            return false;
        }
    }

    /**
     * Function gets actual level of cyan toner (in percents)
     * or returns false if call failed
     *
     * @see getSubUnitPercentageLevel
     * @return int|float|boolean
     */
    public function getCyanTonerLevel()
    {
        if ($this->isColorPrinter()) {
            return $this->getSubUnitPercentageLevel(
                self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_1,
                self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_1
            );
        } else {
            return false;
        }
    }

    /**
     * Function gets actual level of magenta toner (in percents)
     * or returns false if call failed
     *
     * @see getSubUnitPercentageLevel
     * @return int|float|boolean
     */
    public function getMagentaTonerLevel()
    {
        if ($this->isColorPrinter()) {
            return $this->getSubUnitPercentageLevel(
                self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_2,
                self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_2
            );
        } else {
            return false;
        }
    }

    /**
     * Function gets actual level of yellow toner (in percents)
     * or returns false if call failed
     *
     * @see getSubUnitPercentageLevel
     * @return int|float|boolean
     */
    public function getYellowTonerLevel()
    {
        if ($this->isColorPrinter()) {
            return $this->getSubUnitPercentageLevel(
                self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_3,
                self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_3
            );
        } else {
            return false;
        }
    }

    /**
     * Function gets drum level of the printer (in percents)
     * or returns false if call failed
     *
     * @see getSubUnitPercentageLevel
     * @return int|float|boolean
     */
    public function getDrumLevel()
    {
        if ($this->isColorPrinter()) {
            return $this->getSubUnitPercentageLevel(
                self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_5,
                self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_5
            );
        } elseif ($this->isMonoPrinter()) {
            return $this->getSubUnitPercentageLevel(
                self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOT_2,
                self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOT_2
            );
        } else {
            return false;
        }
    }

    /**
     * Function walks through SNMP object ids of Sub-Units and returns results of them all in array
     * with calculated percentage level
     *
     * @return array
     */
    public function getAllSubUnitData()
    {
        $names        = $this->walk(self::SNMP_SUB_UNIT_TYPE_SLOTS);
        $maxValues    = $this->walk(self::SNMP_MARKER_SUPPLIES_MAX_CAPACITY_SLOTS);
        $actualValues = $this->walk(self::SNMP_MARKER_SUPPLIES_ACTUAL_CAPACITY_SLOTS);

        for ($i = 0; $i < sizeOf($names); $i++) {
            $resultData[] = array(
                'name'            => str_replace('"', '', $names[$i]),
                'maxValue'        => $maxValues[$i],
                'actualValue'     => $actualValues[$i],
                'percentageLevel' => ((int)$actualValues[$i] >= 0) ? ($actualValues[$i] / ($maxValues[$i] / 100)) : null
            );
        }
        return $resultData;
    }

    /**
     * Function return all data from functions
     * 
     * @return array
     */
    public function getAllInfo()
    {
        return [
            'current_time' => date('d/m/Y H:i:s'),
            'is_color_printer' => $this->isColorPrinter() ? 'color printer' : 'mono printer',
            'factory_name' => $this->getFactoryId(),
            'vendor' => $this->getVendorName(),
            'serial_number' => $this->getSerialNumber(),
            'black_toner' => $this->getBlackTonerLevel(),
            'cyan_toner'   => $this->getCyanTonerLevel(),
            'magenta_toner' => $this->getMagentaTonerLevel(),
            'yellow_toner' => $this->getYellowTonerLevel(),
            'drum_level' => $this->getDrumLevel(),
            'printed_papers' => $this->getNumberOfPrintedPapers(),
            'black_catridge_type' => $this->getBlackCatridgeType(),
            'cyan_catridge_type' => $this->getCyanCatridgeType(),
            'magenta_catridge_type' => $this->getMagentaCatridgeType(),
            'yellow_catridge_type' => $this->getYellowCatridgeType()
        ];
    }
}
