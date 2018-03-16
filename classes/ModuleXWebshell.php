<?php

/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
  PHP version 5
 * @copyright  Allgäu Infoservice, X-Projects | Benjamin Hummel 2010
 * @author     Allgäu Infoservice, X-Projects | Benjamin Hummel <b.hummel@allgaeu-infoservice.de>
 * @license    GPL
 * @filesource
 */

namespace Contao;

class ModuleXWebshell extends \BackendModule {

    protected $strTemplate = 'be_xwebshell';
    private $xoutput = "";

    public function compile() {

        //CSS und JS einbinden
        $GLOBALS['TL_CSS'][] = 'system/modules/xwebshell/html/xwebshell.css';

        //Rechte Prüfen
        $valid = true;
        $valid = $this->xcheckRights();
        $this->Template->xvalid = $valid;


        if ($valid == true) {


            $cwd = $this->Session->get('cwd');
            if (empty($cwd)) {
                $this->Session->set('cwd', realpath("."));
                $this->Session->set('history', array());
                $this->Session->set('output', '');
            }

            //Command auslesen
            $command = $_POST['command'];

            //Prüfen ob Kommand leer ist!
            if (!empty($command)) {

                //Exitcommand
                if ($command == "exit") {
                    //Rootverzeichnis setzten
                    $this->Session->set('cwd', realpath("."));
                    $this->Session->set('history', array());
                    $this->Session->set('output', '');
                }
                //Kein Exitcommand
                else {
                    //Command und Output und History speichern
                    $history = $this->Session->get('history');
                    $this->xoutput = $this->Session->get('output');

                    //Doppelte Commands entfernen
                    if (($i = array_search($command, $history)) !== false)
                        unset($history[$i]);

                    //Command an den Anfang der Historyliste einfügen
                    array_unshift($history, $command);

                    //Setzten des Outputstrings
                    $this->xoutput .= ' --------------- $ ' . $command . " --------------- \n";

                    $commandtest = substr($command, 0, 3);
                    if ($commandtest == "ftp" || $commandtest == "mys") {
                        //Setzten des Outputstrings
                        $this->xoutput .= "Command not allowed!\n";
                    }
                    //Rootdirektory setzen
                    else if (preg_match('/^[[:blank:]]*cd[[:blank:]]*$/i', $command)) {
                        $this->Session->set('cwd', realpath("."));
                    }
                    //Verzeichniswechsel
                    elseif (preg_match('/^[[:blank:]]*cd[[:blank:]]+([^;]+)$/i', $command, $regs)) {
                        // The current command is a 'cd' command which we have to handle as an internal shell command.
                        if ($regs[1]{0} == '/') {
                            /* Absolute path, we use it unchanged. */
                            $new_dir = $regs[1];
                        } else {
                            // Relative path, we append it to the current working directory.
                            $new_dir = $cwd . '/' . $regs[1];
                        }

                        // Transform '/./' into '/'
                        while (strpos($new_dir, '/./') !== false)
                            $new_dir = str_replace('/./', '/', $new_dir);

                        // Transform '//' into '/'
                        while (strpos($new_dir, '//') !== false)
                            $new_dir = str_replace('//', '/', $new_dir);

                        // Transform 'x/..' into ''
                        while (preg_match('|/\.\.(?!\.)|', $new_dir))
                            $new_dir = preg_replace('|/?[^/]+/\.\.(?!\.)|', '', $new_dir);

                        if ($new_dir == '')
                            $new_dir = '/';

                        // Try to change directory.
                        if (@chdir($new_dir)) {
                            $this->Session->set('cwd', $new_dir);
                        } else {
                            $this->xoutput .= "cd: could not change to: $new_dir\n";
                        }
                    }
                    //Typolightdatenbank dumpen
                    else if ($command == "tl_dump") {
                        $sqlcommand = "";
                        $host = $GLOBALS['TL_CONFIG']['dbHost'];
                        $user = $GLOBALS['TL_CONFIG']['dbUser'];
                        $pw = $GLOBALS['TL_CONFIG']['dbPass'];
                        $db = $GLOBALS['TL_CONFIG']['dbDatabase'];
                        if (!empty($pw)) {
                            $sqlcommand = 'mysqldump -h' . $host . ' -u' . $user . ' -p' . $pw . ' ' . $db . ' > dump_' . date('d-m-Y') . '-' . time() . '.sql';
                        } else {
                            $sqlcommand = 'mysqldump -h' . $host . ' -u' . $user . ' ' . $db . ' > tl_dump_' . date('d-m-Y') . '-' . time() . '.sql';
                        }
                        $this->setCM($sqlcommand);
                    }
                    //Typolightdatenbank dumpen
                    else if ($command == "tl_tar") {
                        $sqlcommand = "";
                        $sqlcommand = 'tar -czvf tl_tar_' . date('d-m-Y') . '-' . time() . '.tar.gz .';
                        $this->setCM($sqlcommand);
                    }
                    //Andere Command als cd
                    else {

                        //Safemode setzen
                        $this->setCM($command);
                    }

                    $this->Session->set('history', $history);
                    $this->Session->set('output', $this->xoutput);
                }
            }

            /* Build the command history for use in the JavaScript */
            $history = $this->Session->get('history');
            if (empty($history)) {
                $this->Template->js_command_hist = '""';
            } else {
                $escaped = array_map('addslashes', $history);
                $this->Template->js_command_hist = '"", "' . implode('", "', $escaped) . '"';
            }
        }
    }

    /**
     * Setzt den Safemode
     */
    private function setCM($command) {

        //Aktuelles Verzecihnis holen
        chdir($this->Session->get('cwd'));

        // We canot use putenv() in safe mode.
        if (!ini_get('safe_mode')) {
            // Advice programs (ls for example) of the terminal size.
            putenv('ROWS=24');
            putenv('COLUMNS=50');
        }

        //Output
        $io = array();
        $p = proc_open($command, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $io);

        /* Read output sent to stdout. */
        while (!feof($io[1])) {
            $this->xoutput .= htmlspecialchars(fgets($io[1]), ENT_COMPAT, 'UTF-8');
        }
        /* Read output sent to stderr. */
        while (!feof($io[2])) {
            $this->xoutput .= htmlspecialchars(fgets($io[2]), ENT_COMPAT, 'UTF-8');
        }

        fclose($io[1]);
        fclose($io[2]);
        proc_close($p);
    }

    /**
     * Pr√ºft die Rechte
     * @return boolean
     */
    private function xcheckRights() {

        //----------------------------------------------------------------------
        // Safemode Checken
        //----------------------------------------------------------------------
        $xsafemode = ini_get("safe_mode");
        if ($xsafemode == 1) {
            return false;
        }

        //----------------------------------------------------------------------
        // Dateirechte checken
        //----------------------------------------------------------------------

        $xfh = @fopen(TL_ROOT . '/xwebshell.txt', 'wb');
        $xcreate_file = is_resource($xfh);
        if ($xcreate_file) {
            @fputs($xfh, 'xwebshell-Test');
            //Filerechte pr√ºfen
            $xfilePermissions = array();
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $xfilePermissions = array(666);
            } else {
                $xfilePermissions = array(664, 644, 660, 640, 755, 777);
            }
            clearstatcache();
            $xfileperms = decoct(@fileperms(TL_ROOT . '/xwebshell.txt') & 511);
            $xok_filerechte = in_array($xfileperms, $xfilePermissions);
            if (!$xok_filerechte) {
                return false;
            }
            @fclose($xfh);
            @unlink(TL_ROOT . '/xwebshell.txt');
        } else {
            return false;
        }

        return true;
    }

}

?>