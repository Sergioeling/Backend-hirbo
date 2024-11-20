<?php
namespace Model;
use DateTime;

class assignDate
{
    static function remove_accents($string) {
        $search = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
        $replace = array('a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'n', 'N');
        return str_replace($search, $replace, $string);
    }

    static function currenDay() {
        $fecha = new DateTime();
        $dayInEnglish = $fecha->format('l');
        $days = array(
            'Monday'    => 'lunes',
            'Tuesday'   => 'martes',
            'Wednesday' => 'miercoles',
            'Thursday'  => 'jueves',
            'Friday'    => 'viernes',
            'Saturday'  => 'sabado',
            'Sunday'    => 'domingo'
        );
        return $days[$dayInEnglish];
    }

    static function getDayValue($day) {
        $days = array(
            'lunes'    => 1,
            'martes'   => 2,
            'miercoles' => 3,
            'jueves'   => 4,
            'viernes'  => 5,
            'sabado'   => 6,
            'domingo'  => 7
        );
        $day = strtolower($day);
        if (array_key_exists($day, $days)) {
            return $days[$day];
        }
        return false;
    }

    static function obtenerPrimerDiaEspecifico($fechaInicio, $fechaFin, $diaSemana) {
        $inicio = $fechaInicio;
        if (!$fechaInicio instanceof DateTime) {
            $inicio = new DateTime($fechaInicio);
        }
        $fin = new DateTime($fechaFin);

        $diasDeLaSemana = [
            'lunes'    => 'Monday',
            'martes'   => 'Tuesday',
            'miercoles' => 'Wednesday',
            'jueves'  => 'Thursday',
            'viernes'    => 'Friday',
            'sabado'  => 'Saturday',
            'domingo'    => 'Sunday'
        ];
        $fecha = clone $inicio;
        while ($fecha <= $fin) {
            if ($fecha->format('l') == $diasDeLaSemana[strtolower($diaSemana)]) {
                return $fecha->format('Y-m-d');
            }
            $fecha->modify('+1 day');
        }
        return null;
    }

    static function buscarHoraDisponible($fechaInicio, $fechaFin, $hrs, $diasOcupados, $HorasOcupadas) {
        $numHoras = sizeof($hrs);
        $indiceSiguienteDia = 0;

        while (true) {
            while ($indiceSiguienteDia < $numHoras) {
                $siguienteDia = $hrs[$indiceSiguienteDia];
                $siguienteDiaFecha = self::obtenerPrimerDiaEspecifico($fechaInicio->format('Y-m-d'), $fechaFin, self::remove_accents($siguienteDia['DIA']));
                $siguienteHoras = explode(',', $siguienteDia['HORAS']);
                $clavesAExtraer = array_keys($diasOcupados, $siguienteDiaFecha);

                if (!empty($clavesAExtraer)) {
                    $clavesAExtraer = array_fill_keys($clavesAExtraer, null);
                    $extraidos = array_intersect_key($HorasOcupadas, $clavesAExtraer);
                    $extraidos = array_merge(...$extraidos);
                    $t = array_diff($siguienteHoras, $extraidos);
                } else {
                    $t = $siguienteHoras;
                }

                if (!empty($t)) {
                    $x = 'Tu entrevista ha sido programada para el día: ' . $siguienteDia['DIA'] . ' ' . $siguienteDiaFecha . ' a las ' . reset($t);
                    return [$x, $siguienteDiaFecha, reset($t)];
                }

                $indiceSiguienteDia++;
            }

            $fechaInicio->modify('+1 week');
            $indiceSiguienteDia = 0;
        }
    }

    static function validateFechaAndHora($fechaInicio, $fechaFin, $hrs, $ocp) {
        $diafinal = '';
        $horas = [];
        $diasOcupados = [];
        $HorasOcupadas = [];
        $indiceDiaValido = null;

        foreach ($hrs as $key => $dia) {
            if (self::getDayValue(self::remove_accents($dia['DIA'])) >= self::getDayValue(self::currenDay())) {
                $diafinal = self::remove_accents($dia['DIA']);
                $horas = explode(',', $dia['HORAS']);
                $indiceDiaValido = $key;
                break;
            }
        }

        if ($indiceDiaValido === null) {
            $diafinal = self::remove_accents($hrs[0]['DIA']);
            $horas = explode(',', $hrs[0]['HORAS']);
        }

        if (!empty($ocp)) {
            if (isset($ocp['DIA'])){
                $antes = $ocp;
                $ocp = [];
                $ocp[]= $antes;
            }
            foreach ($ocp as $ocupados) {
                $diasOcupados[] = self::remove_accents($ocupados['DIA']);
                $HorasOcupadas[] = explode(',', $ocupados['HORARIOS']);
            }
        }
        $x = self::obtenerPrimerDiaEspecifico($fechaInicio, $fechaFin, self::remove_accents($diafinal));
        $indicesAExtraer = array_keys($diasOcupados, $x);
        $clavesAExtraer = array_fill_keys($indicesAExtraer, null);
        $extraidos = array_intersect_key($HorasOcupadas, $clavesAExtraer);
        $extraidos = !empty($extraidos) ? array_merge(...$extraidos) : [];
        $t = array_diff($horas, $extraidos);

        if (!empty($t)) {
            $y = 'Tu entrevista ha sido programada para el día: ' . $diafinal . ' ' . $x . ' a las ' . reset($t);
            return [$y, $x, reset($t)];
        } else {
            return self::buscarHoraDisponible($fechaInicio, $fechaFin, $hrs, $diasOcupados, $HorasOcupadas);
        }
    }
}

