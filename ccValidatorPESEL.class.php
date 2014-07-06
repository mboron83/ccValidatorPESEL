<?php

/**
 * 
 * @author Marcin Boron <http://creativecoder.pl>
 */
class ccValidatorPESEL extends sfValidatorString {
    
    /**
     * 
     * @param array $options - array of setup options
     * @param array $messages - array of validator messages
     */
    protected function configure($options = array(), $messages = array()) {
        parent::configure($options, $messages);
        $this->addRequiredOption('model');
        $this->addRequiredOption('column');
        $this->addRequiredOption('isNew');
        $this->addOption('primary_key', null);
        $this->setMessage('invalid', '"%value%" nie jest poprawnym numerem PESEL');
        $this->addMessage('exists', 'PESEL "%value%" już istnieje w bazie');
    }

    protected function doClean($value) {
        $clean = parent::doClean($value);

        if (count(str_split($clean)) != 11) {
            throw new sfValidatorError($this, 'invalid', array('value' => $value));
        }

        if (!$this->vpesel($clean)) {
            throw new sfValidatorError($this, 'invalid', array('value' => $value));
        }

        $table = Doctrine_Core::getTable($this->getOption('model'));

        $q = Doctrine_Core::getTable($this->getOption('model'))->createQuery('a');

        foreach ($this->getOption('column') as $column) {
            $colName = $table->getColumnName($column);
            $q->addWhere('a.' . $colName . ' = ?', $value);
        }

        $object = $q->fetchOne();
        if ($this->getOption('isNew') && $object) {
            throw new sfValidatorError($this, 'exists', array('value' => $value));
        }

        return $clean;
    }

    private function vpesel($PESEL, $sex = "?") {
        $wk = null;
        if ($PESEL[9] % 2 && $sex == "K")
            return false;
        else if (!($PESEL[9] % 2) && $sex == "M")
            return false;
        $w = array(1, 3, 7, 9);
        for ($i = 0; $i <= 9; $i++) {
            $wk = ($wk + $PESEL[$i] * $w[$i % 4]) % 10;
        }
        $k = (10 - $wk) % 10;
        if ($PESEL[10] == $k)
            return true;
        else
            return false;
    }

    protected function isUpdate(Doctrine_Record $object, $values) {

        foreach ($this->getPrimaryKeys() as $column) {
            if (!isset($values[$column]) || $object->$column != $values[$column]) {
                return false;
            }
        }

        return true;
    }

    protected function getPrimaryKeys() {
        if (null === $this->getOption('primary_key')) {
            $primaryKeys = Doctrine_Core::getTable($this->getOption('model'))->getIdentifier();
            $this->setOption('primary_key', $primaryKeys);
        }

        if (!is_array($this->getOption('primary_key'))) {
            $this->setOption('primary_key', array($this->getOption('primary_key')));
        }

        return $this->getOption('primary_key');
    }

}
