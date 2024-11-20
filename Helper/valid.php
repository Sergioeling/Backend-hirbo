<?php

namespace Helper;

class valid
{
    function validParams($parametros,$inputsNecesary)
    {
        $aproved = false;
        $cont = 0;
        foreach ($parametros as $key => $value){
            if (($value !== '') && in_array($key,$inputsNecesary)){
                $aproved = true;
                $cont++;
            }
        }
        if ($cont< sizeof($inputsNecesary))
            $aproved = false;

        return $aproved;
    }

}