<?php
/**
 * Handles the visualization of GIS POINT objects.
 *
 * @package phpMyAdmin-GIS
 */
class PMA_GIS_Point extends PMA_GIS_Geometry
{
    // Hold the singleton instance of the class
    private static $_instance;

    /**
     * A private constructor; prevents direct creation of object.
     */
    private function __construct()
    {
    }

    /**
     * Returns the singleton.
     *
     * @return the singleton
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            $class = __CLASS__;
            self::$_instance = new $class;
        }

        return self::$_instance;
    }

    /**
     * Scales each row.
     *
     * @param string $spatial spatial data of a row
     *
     * @return array containing the min, max values for x and y cordinates
     */
    public function scaleRow($spatial)
    {
        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        return $this->setMinMax($point, array());
    }

    /**
     * Adds to the PNG image object, the data related to a row in the GIS dataset.
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       Label for the GIS POINT object
     * @param string $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     * @param image  $image       Image object
     *
     * @return the modified image object
     */
    public function prepareRowAsPng($spatial, $label, $point_color, $scale_data, $image)
    {
        // allocate colors
        $black = imagecolorallocate($image, 0, 0, 0);
        $red   = hexdec(substr($point_color, 1, 2));
        $green = hexdec(substr($point_color, 3, 2));
        $blue  = hexdec(substr($point_color, 4, 2));
        $color = imagecolorallocate($image, $red, $green, $blue);

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, $scale_data);

        // draw a small circle to mark the point
        if ($points_arr[0][0] != '' && $points_arr[0][1] != '') {
            imagearc($image, $points_arr[0][0], $points_arr[0][1], 7, 7, 0, 360, $color);
            // print label if applicable
            if (isset($label) && trim($label) != '') {
                imagestring($image, 2, $points_arr[0][0], $points_arr[0][1], trim($label), $black);
            }
        }
        return $image;
    }

    /**
     * Adds to the TCPDF instance, the data related to a row in the GIS dataset.
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       Label for the GIS POINT object
     * @param string $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     * @param image  $pdf         TCPDF instance
     *
     * @return the modified TCPDF instance
     */
    public function prepareRowAsPdf($spatial, $label, $point_color, $scale_data, $pdf)
    {
        // allocate colors
        $red   = hexdec(substr($point_color, 1, 2));
        $green = hexdec(substr($point_color, 3, 2));
        $blue  = hexdec(substr($point_color, 4, 2));
        $line  = array('width' => 1.25, 'color' => array($red, $green, $blue));

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, $scale_data);

        // draw a small circle to mark the point
        if ($points_arr[0][0] != '' && $points_arr[0][1] != '') {
            $pdf->Circle($points_arr[0][0], $points_arr[0][1], 2, 0, 360, 'D', $line);
            // print label if applicable
            if (isset($label) && trim($label) != '') {
                $pdf->SetXY($points_arr[0][0], $points_arr[0][1]);
                $pdf->SetFontSize(7);
                $pdf->Cell(0, 0, trim($label));
            }
        }
        return $pdf;
    }

    /**
     * Prepares and returns the code related to a row in the GIS dataset as SVG.
     *
     * @param string $spatial     GIS POINT object
     * @param string $label       Label for the GIS POINT object
     * @param string $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return the code related to a row in the GIS dataset
     */
    public function prepareRowAsSvg($spatial, $label, $point_color, $scale_data)
    {
        $point_options = array(
            'name'        => $label,
            'id'          => $label . rand(),
            'class'       => 'point vector',
            'fill'        => 'white',
            'stroke'      => $point_color,
            'stroke-width'=> 2,
        );

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, $scale_data);

        $row = '';
        if ($points_arr[0][0] != '' && $points_arr[0][1] != '') {
            $row .= '<circle cx="' . $points_arr[0][0] . '" cy="' . $points_arr[0][1] . '" r="3"';
            foreach ($point_options as $option => $val) {
                $row .= ' ' . $option . '="' . trim($val) . '"';
            }
            $row .= '/>';
        }

        return $row;
    }

    /**
     * Prepares JavaScript related to a row in the GIS dataset
     * to visualize it with OpenLayers.
     *
     * @param string $spatial     GIS POINT object
     * @param int    $srid        Spatial reference ID
     * @param string $label       Label for the GIS POINT object
     * @param string $point_color Color for the GIS POINT object
     * @param array  $scale_data  Array containing data related to scaling
     *
     * @return JavaScript related to a row in the GIS dataset
     */
    public function prepareRowAsOl($spatial, $srid, $label, $point_color, $scale_data)
    {
        $style_options = array(
            'pointRadius'  => 3,
            'fillColor'    => '#ffffff',
            'strokeColor'  => $point_color,
            'strokeWidth'  => 2,
            'label'        => $label,
            'labelYOffset' => -8,
            'fontSize'     => 10,
        );
        if ($srid == 0) {
            $srid = 4326;
        }
        $result = $this->getBoundsForOl($srid, $scale_data);

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($spatial, 6, (strlen($spatial) - 7));
        $points_arr = $this->extractPoints($point, null);

        if ($points_arr[0][0] != '' && $points_arr[0][1] != '') {
            $result .= 'vectorLayer.addFeatures(new OpenLayers.Feature.Vector(('
                . 'new OpenLayers.Geometry.Point(' . $points_arr[0][0] . ', '
                . $points_arr[0][1] . ').transform(new OpenLayers.Projection("EPSG:'
                . $srid . '"), map.getProjectionObject())), null, '
                . json_encode($style_options) . '));';
        }
        return $result;
    }

    /**
     * Generate the WKT with the set of parameters passed by the GIS editor.
     *
     * @param array  $gis_data GIS data
     * @param int    $index    Index into the parameter object
     * @param string $empty    Point deos not adhere to this parameter
     *
     * @return WKT with the set of parameters passed by the GIS editor
     */
    public function generateWkt($gis_data, $index, $empty = '')
    {
         return 'POINT('
             . ((isset($gis_data[$index]['POINT']['x']) && trim($gis_data[$index]['POINT']['x']) != '')
             ? $gis_data[$index]['POINT']['x'] : '') . ' '
             . ((isset($gis_data[$index]['POINT']['y']) && trim($gis_data[$index]['POINT']['y']) != '')
             ? $gis_data[$index]['POINT']['y'] : '') . ')';
    }

    /**
     * Generate the WKT for the data from ESRI shape files.
     *
     * @param array $row_data GIS data
     *
     * @return the WKT for the data from ESRI shape files
     */
    public function getShape($row_data) {
        return 'POINT(' . (isset($row_data['x']) ? $row_data['x'] : '')
             . ' ' . (isset($row_data['y']) ? $row_data['y'] : '') . ')';
    }

    /**
     * Generate parameters for the GIS data editor from the value of the GIS column.
     *
     * @param string $value of the GIS column
     * @param index  $index of the geometry
     *
     * @return  parameters for the GIS data editor from the value of the GIS column
     */
    public function generateParams($value, $index = -1)
    {
        if ($index == -1) {
            $index = 0;
            $params = array();
            $data = PMA_GIS_Geometry::generateParams($value);
            $params['srid'] = $data['srid'];
            $wkt = $data['wkt'];
        } else {
            $params[$index]['gis_type'] = 'POINT';
            $wkt = $value;
        }

        // Trim to remove leading 'POINT(' and trailing ')'
        $point = substr($wkt, 6, (strlen($wkt) - 7));
        $points_arr = $this->extractPoints($point, null);

        $params[$index]['POINT']['x'] = $points_arr[0][0];
        $params[$index]['POINT']['y'] = $points_arr[0][1];

        return $params;
    }
}
?>
