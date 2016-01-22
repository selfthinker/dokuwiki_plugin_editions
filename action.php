<?php
/**
 * DokuWiki Editions Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to interfere with the event system
 * need to inherit from this class
 */
class action_plugin_editions extends DokuWiki_Action_Plugin {

    // register hooks
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT','BEFORE', $this, 'addIcons');
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'openContent');
        $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'closeContent');
        $controller->register_hook('PLUGIN_PURPLENUMBERS_P_OPENED', 'BEFORE', $this, 'openSection');
        $controller->register_hook('PLUGIN_PURPLENUMBERS_P_CLOSED', 'AFTER', $this, 'closeSection');
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'AFTER', $this, 'cleanDocument');
    }


    /**
     * Add lang and class around all content if in edition
     */
    function openContent(&$event, $param){
        if ($this->_isEdition()) {
            echo '<div '.$this->_getLang().' class="editions_edition">';
        }
    }

    /**
     * Close content div from openContent()
     */
    function closeContent(&$event, $param){
        if ($this->_isEdition()) {
            echo '</div>';
        }
    }

    /**
     * Add div around each paragraph
     */
    function openSection(&$event, $param) {
        $pid = $event->data['pid'];
        if ($this->_isEdition() && $pid) {
            $event->data['doc'] .= '<div class="editions_section">';
        }
    }

    /**
     * Add edition links below each paragraph and close div from openSection()
     */
    function closeSection(&$event, $param){
        $pid = $event->data['pid'];
        if ($this->_isEdition() && $pid) {
            $event->data['doc'] .= $this->_getEditionLinks($pid) . '</div>';
        }
    }

    /**
     * Remove open divs from empty paragraphs
     */
    function cleanDocument(&$event, $param){
        if ($this->_isEdition()) {
            $event->data = preg_replace('/(<div class="editions_section">\s*){2}/','<div class="editions_section">',$event->data);
        }
    }

    /**
     * Add icons to edition links
     */
    function addIcons(&$event, $param) {
        $pluginDir = DOKU_BASE.'lib/plugins/editions/';

        $CSS = '';
        foreach ($this->_getEditions() as $edition => $lang) {
            $CSS .= '.editions_editionlist a.'.$edition.' { background-image: url('.$pluginDir.'images/'.$edition.'.png); }'.DOKU_LF;
        }

        if (!empty($CSS)){
            $event->data['style'][] = array(
                'type'    => 'text/css',
                'media'   => 'screen',
                '_data'   => $CSS
            );
        }
    }


    /**
     * Get links to same paragraph in all editions
     */
    function _getEditionLinks($pid) {
        global $ID;

        $editionLinks = '<div class="editions_editionlist">';

        // links to other editions
        $editionLinks .= '<ul>';
        foreach ($this->_getEditions() as $edition => $lang) {
            if (curNS($ID)!=$edition) {
                $eLink = wl($this->getConf('editionNamespace').':'.$edition.':'.noNS($ID)).'#'.$pid;
                $editionLinkTitle = sprintf($this->getLang('editionLinkTitle'), ucfirst($edition));
                $editionLinks .= '<li>'.tpl_link($eLink,ucfirst($edition),'class="'.$edition.'" title="'.$editionLinkTitle.'"',1).'</li>';
            }
        }
        $editionLinks .= '</ul>';

        // where to load the snippet
        $editionLinks .= '<div id="load__'.$pid.'" class="editions_snippet JSpopup"></div>';

        $editionLinks .= '</div>';

        return $editionLinks;
    }

    /**
     * Get editions (and their language) from config file
     */
    function _getEditions() {
        $editionFile = DOKU_CONF.'editions.conf';
        if (@file_exists($editionFile)) {
            return confToHash($editionFile);
        }
        return array();
    }

    /**
     * Check if current page is part of an edition
     */
    function _isEdition() {
        global $ID;
        global $ACT;
        global $conf;
        $includeStartpage = $this->_getPurpleNumbersConf();

        if (!$includeStartpage && (noNS($ID) == $conf['start'])) return false;
        $curRootNS = substr($ID, 0, strpos($ID,':'));
        if ( ($curRootNS == $this->getConf('editionNamespace')) && ($ACT=='show') ) return true;
        return false;
    }

    /**
     * Get language string for current edition
     */
    function _getLang() {
        global $ID;

        if ($this->_isEdition()) {
            $editions = $this->_getEditions();
            if (array_key_exists(curNS($ID), $editions)) {
                $lang = $editions[curNS($ID)];
                return ' lang="'.$lang.'" xml:lang="'.$lang.'"';
            }
        }
        return '';
    }

    /**
     * Get 'includeStartpage' config setting from purplenumbers plugin
     */
    function _getPurpleNumbersConf() {
        if(!plugin_isdisabled('purplenumbers')) {
            $purplenumbers =& plugin_load('renderer', 'purplenumbers');
            return $purplenumbers->getConf('includeStartpage');
        }
        return false;
    }

}

// vim:ts=4:sw=4:
