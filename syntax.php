<?php
/**
 * DokuWiki Plugin publish (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Jarrod Lowe <dokuwiki@rrod.net>
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */


// must be run within DokuWiki
if(!defined('DOKU_INC')) die();


class syntax_plugin_publish extends DokuWiki_Syntax_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct(){
        $this->hlp = plugin_load('helper','publish');
    }

    function pattern() {
        return '\[APPROVALS.*?\]';
    }

    function getType() {
        return 'substition';
    }

    function getSort() {
        return 20;
    }

    function PType() {
        return 'block';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern(),$mode,'plugin_publish');
    }

    function handle($match, $state, $pos, Doku_Handler $handler){
        $namespace = substr($match, 11, -1);
        return array($match, $state, $pos, $namespace);
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        global $conf;

        if($mode != 'xhtml') {
            return false;
        }

        list($match, $state, $pos, $namespace) = $data;

        $namespace = cleanID(getNS($namespace . ":*"));

        $pages = $this->hlp->getPagesFromNamespace($namespace);

        if(count($pages) == 0) {
            $renderer->doc .= '<p class="apr_none">' . $this->getLang('apr_p_none') . '</p>';
            return true;
        }

        usort($pages, array($this,'_pagesorter'));

        // Output Table
        $renderer->doc .= '<table class="apr_table"><tr class="apr_head">';
        $renderer->doc .= '<th class="apr_page">' . $this->getLang('apr_p_hdr_page') . '</th>';
        $renderer->doc .= '<th class="apr_prev">' . $this->getLang('apr_p_hdr_previous') . '</th>';
        $renderer->doc .= '<th class="apr_upd">' . $this->getLang('apr_p_hdr_updated') . '</th>';
        $renderer->doc .= '</tr>';


        $working_ns = null;
        foreach($pages as $page) {
            // $page: 0 -> pagename, 1 -> approval metadata, 2 -> last changed date
            $this_ns = getNS($page[0]);

            if($this_ns != $working_ns) {
                $name_ns = $this_ns;
                if($this_ns == '') { $name_ns = 'root'; }
                $renderer->doc .= '<tr class="apr_ns"><td colspan="3"><a href="';
                $renderer->doc .= wl($this_ns . ':' . $this->getConf('start'));
                $renderer->doc .= '">';
                $renderer->doc .= $name_ns;
                $renderer->doc .= '</a> ';
                $renderer->doc .= '<button class="publish__approveNS" type="button" ns="' . $name_ns .'">' . $this->getLang('approveNS') . '</button>';
                $renderer->doc .= '</td></tr>';
                $working_ns = $this_ns;
            }

            $updated = '<a href="' . wl($page[0]) . '">' . dformat($page[2]) . '</a>';
            if($page[1] == null || count($page[1]) == 0) {
                // Has never been approved
                $approved = '';
            }else{
                $keys = array_keys($page[1]);
                sort($keys);
                $last = $keys[count($keys)-1];
                $approved = sprintf($this->getLang('apr_p_approved'),
                    $page[1][$last][1],
                    wl($page[0], 'rev=' . $last),
                    dformat($last));
                if($last == $page[2]) { $updated = 'Unchanged'; } //shouldn't be possible:
                //the search_helper should have
                //excluded this
            }

            $renderer->doc .= '<tr class="apr_table';
            if($approved == '') { $renderer->doc .= ' apr_never'; }
            $renderer->doc .= '"><td class="apr_page"><a href="';
            $renderer->doc .= wl($page[0]);
            $renderer->doc .= '">';
            $renderer->doc .= $page[0];
            $renderer->doc .= '</a></td><td class="apr_prev">';
            $renderer->doc .= $approved;
            $renderer->doc .= '</td><td class="apr_upd">';
            $renderer->doc .= $updated;
            $renderer->doc .= '</td></tr>';

            //$renderer->doc .= '<tr><td colspan="3">' . print_r($page, true) . '</td></tr>';
        }
        $renderer->doc .= '</table>';
        return true;
    }



    /**
     * Custom sort callback
     */
    function _pagesorter($a, $b){
        $ac = explode(':',$a[0]);
        $bc = explode(':',$b[0]);
        $an = count($ac);
        $bn = count($bc);

        // Same number of elements, can just string sort
        if($an == $bn) { return strcmp($a[0], $b[0]); }

        // For each level:
        // If this is not the last element in either list:
        //   same -> continue
        //   otherwise strcmp
        // If this is the last element in either list, it wins
        $n = 0;
        while(true) {
            if($n + 1 == $an) { return -1; }
            if($n + 1 == $bn) { return 1; }
            $s = strcmp($ac[$n], $bc[$n]);
            if($s != 0) { return $s; }
            $n += 1;
        }
    }

}

