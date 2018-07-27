<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_sleat\output;

use coding_exception;
use html_writer;
use tabobject;
use tabtree;
use custom_menu_item;
use custom_menu;
use block_contents;
use navigation_node;
use action_link;
use stdClass;
use moodle_url;
use preferences_groups;
use action_menu;
use help_icon;
use single_button;
use single_select;
use paging_bar;
use url_select;
use context_course;
use pix_icon;
use context_system;
use action_menu_filler;
use action_menu_link_secondary;
use core_text;

use htm_slider;
use htm_slide;
use htm_quicklinks;
use htm_quicklink_item;

require_once($CFG->dirroot . '/theme/sleat/classes/slider.php');
require_once($CFG->dirroot . '/theme/sleat/classes/quicklinks.php');


defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_sleat
 * @copyright  2012 Bas Brands, www.basbrands.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_renderer extends \core_renderer {

    /**
     * This code renders the navbar button to control the display of the custom menu
     * on smaller screens.
     *
     * Do not display the button if the menu is empty.
     *
     * @return string HTML fragment
     */
    public function navbar_button() {
        global $CFG;

        $iconbar = html_writer::tag('span', '', array('class' => 'icon-bar'));
        $sronly = html_writer::tag('span', get_string('togglenavigation', 'core'), array('class' => 'sr-only'));
        $button = html_writer::tag('button', $iconbar . "\n" . $iconbar. "\n" . $iconbar . $sronly, array(
            'class'       => 'navbar-toggle',
            'type'        => 'button',
            'data-toggle' => 'collapse',
            'data-target' => '#totara-navbar'
        ));

        return $button;
    }

    /**
     * Returns a search box.
     *
     * @param  string $id     The search box wrapper div id, defaults to an autogenerated one.
     * @return string         HTML with the search form hidden by default.
     */
    public function search_box($id = false) {
        global $CFG;

        // Accessing $CFG directly as using \core_search::is_global_search_enabled would
        // result in an extra included file for each site, even the ones where global search
        // is disabled.
        if (empty($CFG->enableglobalsearch) || !has_capability('moodle/search:query', context_system::instance())) {
            return '';
        }

        if ($id == false) {
            $id = uniqid();
        } else {
            // Needs to be cleaned, we use it for the input id.
            $id = clean_param($id, PARAM_ALPHANUMEXT);
        }


        $context = new stdClass();
        $context->id = $id;
        $context->searchurl = $CFG->wwwroot . '/search/index.php';
        $context->labeltext = get_string('enteryoursearchquery', 'search');
        $context->inputplaceholder = get_string('search', 'search');

        return $this->render_from_template('theme_sleat/header-search', $context);
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => ''));
            $html .= html_writer::start_tag('div', array('class' => 'container'));
                $html .= html_writer::start_tag('div', array('class' => 'col-xs-12'));
                    $html .= html_writer::start_div('clearfix', array('id' => 'page-navbar'));
                        $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
                        $html .= html_writer::div($this->page_heading_button(), 'breadcrumb-button ');
                    $html .= html_writer::end_div();
                    $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
                $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('header');
        return $html;
    }

    /**
     * Construct a user menu, returning HTML that can be echoed out by a
     * layout file.
     *
     * @param stdClass $user A user object, usually $USER.
     * @param bool $withlinks true if a dropdown should be built.
     * @return string HTML fragment.
     */
    public function user_menu($user = null, $withlinks = null) {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        if (is_null($user)) {
            $user = $USER;
        }

        // Note: this behaviour is intended to match that of core_renderer::login_info,
        // but should not be considered to be good practice; layout options are
        // intended to be theme-specific. Please don't copy this snippet anywhere else.
        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        // Add a class for when $withlinks is false.
        $usermenuclasses = 'usermenu';
        if (!$withlinks) {
            $usermenuclasses .= ' withoutlinks';
        }

        $returnstr = "";

        // If during initial install, return the empty return string.
        if (during_initial_install()) {
            return $returnstr;
        }

        $loginpage = $this->is_login_page();
        $loginurl = get_login_url();
        // If not logged in, show the typical not-logged-in string.
        if (!isloggedin()) {
            $returnstr = get_string('loggedinnot', 'moodle');
            if (!$loginpage) {
                $returnstr .= " <a href=\"$loginurl\" class=\"btn btn-primary btn-xs\">" . get_string('login') . '</a>';
            }
            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );

        }

        // If logged in as a guest user, show a string to that effect.
        if (isguestuser()) {
            $returnstr = get_string('loggedinasguest');
            if (!$loginpage && $withlinks) {
                $returnstr .= " <a href=\"$loginurl\" class=\"btn btn-primary btn-xs\">" . get_string('login') . '</a>';
            }

            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );
        }

        // Get some navigation opts.
        $opts = user_get_user_navigation_info($user, $this->page);

        $avatarclasses = "avatars";
        $avatarcontents = html_writer::span($opts->metadata['useravatar'], 'avatar current');
        $usertextcontents = $opts->metadata['userfullname'];

        // Other user.
        if (!empty($opts->metadata['asotheruser'])) {
            $avatarcontents .= html_writer::span(
                $opts->metadata['realuseravatar'],
                'avatar realuser'
            );
            $usertextcontents = $opts->metadata['realuserfullname'];
            $usertextcontents .= html_writer::tag(
                'span',
                get_string(
                    'loggedinas',
                    'moodle',
                    html_writer::span(
                        $opts->metadata['userfullname'],
                        'value'
                    )
                ),
                array('class' => 'meta viewingas label label-info')
            );
        }

        // Role.
        if (!empty($opts->metadata['asotherrole'])) {
            $role = core_text::strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['rolename'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['rolename'],
                'meta role role-' . $role . ' label label-info'
            );
        }

        // User login failures.
        if (!empty($opts->metadata['userloginfail'])) {
            $usertextcontents .= html_writer::span(
                $opts->metadata['userloginfail'],
                'meta loginfailures label label-info'
            );
        }

        // MNet.
        if (!empty($opts->metadata['asmnetuser'])) {
            $mnet = strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['mnetidprovidername'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['mnetidprovidername'],
                'meta mnet mnet-' . $mnet . ' label label-info'
            );
        }

        $returnstr .= html_writer::span(
            html_writer::span($usertextcontents, 'usertext hidden-xs hidden-sm') .
            html_writer::span($avatarcontents, $avatarclasses),
            'userbutton'
        );

        // Create a divider (well, a filler).
        $divider = new action_menu_filler();
        $divider->primary = false;

        $am = new action_menu();
        $am->set_menu_trigger(
            $returnstr
        );
        $am->set_alignment(action_menu::TR, action_menu::BR);
        $am->set_nowrap_on_items();
        if ($withlinks) {
            $navitemcount = count($opts->navitems);
            $idx = 0;
            foreach ($opts->navitems as $key => $value) {

                switch ($value->itemtype) {
                    case 'divider':
                        // If the nav item is a divider, add one and skip link processing.
                        $am->add($divider);
                        break;

                    case 'invalid':
                        // Silently skip invalid entries (should we post a notification?).
                        break;

                    case 'link':
                        // Process this as a link item.
                        $pix = null;
                        if (isset($value->pix) && !empty($value->pix)) {
                            $pix = new pix_icon($value->pix, $value->title, null, array('class' => 'iconsmall'));
                        } else if (isset($value->imgsrc) && !empty($value->imgsrc)) {
                            $value->title = html_writer::img(
                                $value->imgsrc,
                                $value->title,
                                array('class' => 'iconsmall')
                            ) . $value->title;
                        }

                        $al = new action_menu_link_secondary(
                            $value->url,
                            $pix,
                            $value->title,
                            array('class' => 'icon')
                        );
                        if (!empty($value->titleidentifier)) {
                            $al->attributes['data-title'] = $value->titleidentifier;
                        }
                        $am->add($al);
                        break;
                }

                $idx++;

                // Add dividers after the first item and before the last item.
                if ($idx == 1 || $idx == $navitemcount - 1) {
                    $am->add($divider);
                }
            }
        }

        return html_writer::div(
            $this->render($am),
            $usermenuclasses
        );
    }


    
    /*****************************************/
    /* HTM FUNCTIONS */
    /*****************************************/

    /**
     * Get toggle status of a toggle setting
     * 
     * @param string $setting the name of the setting as defined in setting.php
     * @return boolean
     */
    public function get_toggle_status($setting) {
        GLOBAL $PAGE;
        $togglestatus = $PAGE->theme->settings->$setting;
        if ($togglestatus == 1 || $togglestatus == 2 && !isloggedin() || $togglestatus == 3 && isloggedin()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get value of a non image setting
     * 
     * @param string $setting the name of the setting as defined in setting.php
     * @return string
     */
    public function get_setting($setting) {
        GLOBAL $PAGE;
        $value = $PAGE->theme->settings->$setting;
        return $value;
    }

    /**
     * Get url location of a stored file setting
     * 
     * @param string $setting the name of the setting as defined in setting.php
     * @return string
     */
    public function get_setting_img($setting) {
        GLOBAL $PAGE;
        $value = $PAGE->theme->setting_file_url($setting, $setting);
        return $value;
    }

    /**
     * htm_fp_slideshow
     * Renders the content for the frontpage slideshow to be passed into the mustache tmeplate
     * 
     * @return string
     */
    public function htm_fp_slideshow() {
        $name = 'frontpage';

        $slideshow = new htm_slider($name);
        $slideshow->add_status($this->get_toggle_status("{$name}_slideshow_toggle"));

        switch ($this->get_setting("{$name}_slideshow_transition")) {
            case 'fade':
                $slideshow->add_setting('transition', 'slide carousel-fade');
                break;
            case 'horizontal':
                $slideshow->add_setting('transition', 'slide');
        }
        $slideshow->add_setting('interval', $this->get_setting("{$name}_slideshow_time_per_slide"));
        $slideshow->add_setting('controls', $this->get_setting("{$name}_slideshow_controls"));
        $slideshow->add_setting('pager', $this->get_setting("{$name}_slideshow_pager"));

        $count = $this->get_setting("{$name}_slideshow_count");
        for ($i = 1; $i <= $count; $i++) {
            $data = new stdClass();
            $data->titleSmall = $this->get_setting("{$name}_slideshow_{$i}_title_small");
            $data->titleLarge = $this->get_setting("{$name}_slideshow_{$i}_title_large");
            $data->summary = $this->get_setting("{$name}_slideshow_{$i}_summary");
            if( ! empty( $this->get_setting( "{$name}_slideshow_{$i}_show_search" ) ) ) {
                $data->showSearch = $this->get_setting( "{$name}_slideshow_{$i}_show_search" );
                $data->searchPlaceholder = $this->get_setting( "{$name}_slideshow_{$i}_search_placeholder" );
                $data->reportId = $this->get_setting( "{$name}_slideshow_{$i}_search_report_id" );
            }
           
            $data->hasimg = false;
            if (!empty($this->get_setting_img("{$name}_slideshow_{$i}_image"))) {
                $data->hasimg = true;
                $data->image = $this->get_setting_img("{$name}_slideshow_{$i}_image");
            }

            if ($data->hasimg) {
                $data->visible = true;
            }
            
            
            $slide = new htm_slide($data, $i);

            $slideshow->add_slide($slide);
        }
        if ($slideshow->count < 2) {
            $slideshow->add_setting('controls', 0);
            $slideshow->add_setting('pager', 0);
        }
        return $slideshow;
    }


    /**
     * htm_display_logo_carousel
     * Renders the content for the logo_carousel carousel above the footer, to be passed into the
     * relevant mustache template
     * 
     * @return string
     */
    public function htm_display_logo_carousel() {

        GLOBAL $PAGE;

        $context = new StdClass;

        $context->status = $this->get_toggle_status("frontpage_accreditations_toggle");
        $context->bgcolor = $this->get_setting("Accreditations_section_background_colour");
        $logos = [];
 
        $logoCount = $this->get_setting("frontpage_accreditations_count");

        for($i = 1; $i <= $logoCount; $i++) {

            $logo = new StdClass();

            $logo->image = $this->get_setting_img("frontpage_accreditations_{$i}_image");

            array_push($logos, $logo);

        }

        $context->data = $logos;
        return $context;
    }

    public function htm_display_fpc() {
        $fpc = new stdClass();
        
        $fpc->status = $this->get_toggle_status("frontpage_content_toggle");
        $fpc->title = $this->get_setting('frontpage_content_title');
        $fpc->text = $this->get_setting('frontpage_content_text');

        return $fpc;
    }

    public function htm_display_quicklinks($name) {
        $quicklinks = new htm_quicklinks($name);
        $quicklinks->add_status($this->get_toggle_status("{$name}_quicklink_toggle"));

        $quicklinks->bgcolor = $this->get_setting("fp_ql_section_background_colour");

        $count = $this->get_setting("{$name}_quicklink_count");
        for ($i = 1; $i <= $count; $i++) {
            $data = new stdClass();
            
            $data->title = $this->get_setting("{$name}_quicklink_{$i}_title");
            $data->text = $this->get_setting("{$name}_quicklink_{$i}_text");
            $data->textcolour = $this->get_setting("{$name}_quicklink_{$i}_text_colour");

            $data->hasimg = false;
            if (!empty($this->get_setting_img("{$name}_quicklink_{$i}_image"))) {
                $data->hasimg = true;
                $data->image = $this->get_setting_img("{$name}_quicklink_{$i}_image");
                $data->imagewidth = $this->get_setting("{$name}_quicklink_{$i}_image_width");
                $data->imageheight = $this->get_setting("{$name}_quicklink_{$i}_image_height");
                $data->imagetype = $this->get_setting("{$name}_quicklink_{$i}_image_type");
            }
            if (!empty($this->get_setting("{$name}_quicklink_{$i}_url"))) {
                $data->url = $this->get_setting("{$name}_quicklink_{$i}_url");
            }
            

            $item = new htm_quicklink_item($data, $i);

            $quicklinks->add_item($item);
        }
        return $quicklinks;
    }

    public function htm_get_sm_urls() {
        $data = new stdClass();
        $data->profiles = [];
        $fb = new stdClass();
        $fb->url = $this->get_setting("facebook_url");
        $fb->icon = 'facebook';
        
        $twitter = new stdClass();
        $twitter->url = $this->get_setting("twitter_url");
        $twitter->icon = 'twitter';

        $linkedin = new stdClass();
        $linkedin->url = $this->get_setting("linkedin_url");
        $linkedin->icon = 'linkedin';

        $yt = new stdClass();
        $yt->url = $this->get_setting("youtube_url");
        $yt->icon = 'youtube';

        if (!empty($fb)) {
            array_push($data->profiles, $fb);
        }
        
        if (!empty($twitter)) {
            array_push($data->profiles, $twitter);
        }

        if (!empty($linkedin)) {
            array_push($data->profiles, $linkedin);
        }

        if (!empty($yt)) {
            array_push($data->profiles, $yt);
        }

        return $data;
    }

    public function htm_display_footer() {
        $data = new stdClass();

        $data->col1Title = $this->get_setting( 'footer_title_col_1' );
        $data->col1Text = $this->get_setting( 'footer_text_col_1' );

        $data->col2Title = $this->get_setting( 'footer_title_col_2' );
        $data->col2Text = $this->get_setting( 'footer_text_col_2' );

        $data->col3Title = $this->get_setting( 'footer_title_col_3' );
        $data->col3Text = $this->get_setting( 'footer_text_col_3' );

        $data->footnote = $this->get_setting("footer_footnote");

        return $data;
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_width_full_header() {
        /*$html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => ''));
            $html .= html_writer::start_tag('div', array('class' => 'container'));
                $html .= html_writer::start_tag('div', array('class' => 'row'));
                    $html .= html_writer::start_div('clearfix', array('id' => 'page-navbar'));
                        $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
                        $html .= html_writer::div($this->page_heading_button(), 'breadcrumb-button ');
                    $html .= html_writer::end_div();
                    $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
                $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('header');
        return $html;*/

        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'page-header'));

            // Page navbar
            $html .= html_writer::start_tag('div', array('class' => 'container'));
                $html .= html_writer::start_tag('div', array('class' => 'col-xs-12'));
                    $html .= html_writer::start_div('clearfix', array('id' => 'page-navbar'));
                        $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
                        $html .= html_writer::div($this->page_heading_button(), 'breadcrumb-button ');
                    $html .= html_writer::end_div();
                    $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
                $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('header');
        return $html;

    }

    
}