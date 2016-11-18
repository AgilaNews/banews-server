<?php
class SimhashComparator
{
    protected $deviation;

    public function __construct($deviation = 4) {
        $this->deviation = $deviation;
    }

    public function compare($fp1, $fp2) {
        if (strlen($fp1) !== strlen($fp2)) {
            throw new \LogicException(sprintf(
                'The simhash values have different sizes (%s bits and %s bits).',
                strlen($fp1), strlen($fp2)
            ));
        }

        $hammingDistance = $this->calHammingDistance($fp1, $fp2);
        return $this->calSimilarity($hammingDistance);
    }
    
    protected function calHammingDistance($a, $b){
        $comp = array();
        for($i=0; $i<strlen($fp1); $i++){
            if ($a[$i] != $b[$i]){
                $comp[] = '1';
            } else {
                $comp[] = '0';
            }
        }
        return substr_count(implode('', $comp), '1');
    }

    protected function calSimilarity($hammingDistance) {
        return $this->gaussianDensity($hammingDistance) /
            $this->gaussianDensity(0);
    }

    protected function gaussianDensity($x) {
        $y = -(1/2) * pow($x/$this->deviation, 2);
        $y = exp($y);
        return (1/sqrt(2*pi())) * $y;
    }
}


