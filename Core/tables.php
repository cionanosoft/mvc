<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

/**
 * Description of tables
 *
 * @author jorge.vasquez
 */
class S_TABLE
{

    public function __construct()
    {
        
    }

    public function __clone()
    {
        
    }

    public static function create($id, $cols)
    {
        echo '
            <table class="table table-striped table-hover w-100 no-footer" id="'.$id.'">
                <thead>
                    <tr>
        ';
        
        foreach ($cols as $col)
        {
            echo '<th>' . $col . '</th>';
        }

        echo '
                    </tr>
                </thead>
                <tbody>
        ';

    }
    
    public static function close() {
        echo '
                </tbody>
            </table>
        ';
    }

}
