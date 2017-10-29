<?php

/** @file html.php
 * HTML generator.
 * @author xandri03
 * @author xsemri00
 */



/** HTML page. */
/** {{{ */
class Page {
    /** Global menubar element */
    static private $global_menu =
    '<div class="menu column">
    <ul>
        <li><a href="signup.php">signup</a></li>
        <li><a href="signin.php">signin</a></li>
    </ul></div>'
    ;

    /** A collection of primitives. */
    private $primitives;

    /** Page header text. */
    private $header;

    /** Page menu bar. */
    private $menu;

    /** Initialize the page. */
    public function __construct($header = 'Some Header', $menu = null) {
        $this->header = $header;
        $this->menu =$menu == null ? self::$global_menu : $menu;
        $this->primitives = array();
    }

    /** Register a primitive.
     * @param primitive instance of a class implementing Primitive
     */
    public function add($primitive) {
        array_push($this->primitives, $primitive);
    }

    /** Add a newline. */
    public function newline() {
        $this->add(new CRLF());
    }

    /** html() implementation. */
    public function html() {
        // Start the page
        $page = "<!DOCTYPE html> <html>";

        // Head + styles
        $page .= '<head><link rel="stylesheet" href="styles.css"></head>';
        // Body
        $page .= '<body>';

        $page .= "<div class=\"header\"><h1>$this->header</h1></div>";
        $page .= '<div class="clearfix">';

        // menu
        $page .= $this->menu;
        $page .= '<div class="content column">';
        foreach ($this->primitives as $primitive) {
            $page .= $primitive->html();
        }
        $page .= "</div>";
        $page .= "</div>";
        $page .= '<div class="footer">IIS VUT FIT 2017</div>';
        $page .= "</body>";

        // End the page
        $page .= "</html>";

        // Success
        return $page;
    }

    /** Render the page. */
    public function render() {
        echo($this->html());
    }
}
/** }}} */

/****************************** PRIMITIVES ******************************/

/** HTML primitive abstract class. */
abstract class Primitive {
    /** tag element attributes */
    protected $attributes;

    public function set($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    public function get($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    /** iterate over attributes and returns it in html notation */
    protected function attr_html() {
        $str = " ";
        foreach($this->attributes as $attribute => $value) {
            $str .= " $attribute='$value' ";
        }
        return $str;
    }

    public function __construct() {
        $this->attributes = array();
    }

    /** Get html representation of a primitive. */
    abstract public function html();
}


class Block extends Primitive {
    private $childs;

    public function __construct() {
        // XXX inline (span)?
        parent::__construct();
        $this->childs = array();
    }

    public function add($primitive) {
        array_push($this->childs, $primitive);
    }

    public function html() {
        $ret = '<div ' . $this->attr_html() . '>';
        foreach ($this->childs as $primitive) {
            $ret .= $primitive->html();
        }
        $ret .= '</div>';
        return $ret;
    }
}


/** Line break. */
class CRLF extends Primitive {
    /** html() implementation. */
    public function html() {
        return "</br>";
    }
}

/** Text block. */
class Text extends Primitive {
    /** Block data. */
    private $data;

    /** Construct a text block. */
    public function __construct($data) {
        parent::__construct();
        $this->data = $data;
    }

    /** html() implementation. */
    public function html() {
        return "<span>$this->data</span>";
    }
}

/** Hyperlink. */
class Link extends Primitive {
    /** Target URL. */
    private $url;
    /** Link description. */
    private $description;

    /** Create a hyperlink. */
    public function __construct($url, $description) {
        parent::__construct();
        $this->url = $url;
        $this->description = $description;
    }

    /** html() implementation. */
    public function html() {
        return "<a " . $this->attr_html() .
               " href='$this->url'>$this->description</a>";
    }
}

/** Image. */
class Image extends Primitive {
    /** Source image. */
    private $source;

    /** Create an image. */
    public function __construct($source) {
        parent::__construct();
        $this->source = $source;
    }

    /** html() implementation. */
    public function html() {
        return "<img src='$this->source' style='width:20%;height:auto;'>";
    }
}

/** Form input. */
class Input extends Primitive {
    /** Input type. */
    private $type;
    /** Input identifier. */
    protected $name;
    /** Input element label (optional). */
    protected $label;
    /** Additional attributes (optional). */
    protected $attributes;

    /** Construct a form input. */
    public function __construct($type, $name, $label = null) {
        parent::__construct();
        $this->type = $type;
        $this->name = $name;
        $this->label = $label;
    }

    /** html() implementation. */
    public function html() {
        $str = '';
        // Add label (if exists)
        if(isset($this->label)) {
            $str .= "<label for='$this->name'>$this->label</label>";
        }

        // Open tag, append attributes, close tag
        $str .= "<input type='$this->type' name='$this->name' ";
        $str .= $this->attr_html();
        $str .=  "/>";

        // Success
        return $str;
    }
}

/** Select: a special input. */
class Select extends Input {
    /** List of options. */
    private $options;
    /** Selected option (optional). */
    private $selected;

    /** Construct a select input. */
    public function __construct($name, $label = null) {
        Input::__construct("select", $name, $label);
    }

    /** Add new option.
     * @param option new option
     * @param description option description
     * @note if @c option was already registered, its description will be overwritten
     */
    public function add_option($option, $description) {
        $this->options[$option] = $description;
    }

    /** Specify a preselected value.
     * @param option name of an option to select
     */
    public function select($option) {
        $this->selected = $option;
    }

    /** html() implementation. */
    public function html() {

        $str = "";
        // Add label (if exists)
        if(isset($this->label)) {
            $str .= "<label for='$this->name'>$this->label</label>";
        }

        // Open tag, append attributes, close tag
        $str .= "<select name='$this->name' ";
        $str .= $this->attr_html();
        $str .= ">";

        // Append options, close element
        foreach($this->options as $option => $description) {
            $str .= "<option value='$option' ";
            if($this->selected == $option) {
                $str .= " selected ";
            }
            $str .= ">$description</option>";
        }
        $str .= "</select>";

        // Success
        return $str;
    }
}

/** HTML Form. */
class Form extends Primitive {
    /** A collection of form inputs. */
    private $inputs;

    /** Error message (optional). */
    private $error;

    /** Create a form. */
    public function __construct() {
        parent::__construct();
        $this->inputs = array();
        $this->error = null;
    }

    /** Add a form input.
     * @param input instace of Input class
     */
    public function add($input) {
        array_push($this->inputs, $input);
    }

    /** Register error message. */
    public function add_error($error) {
        $this->error = $error;
    }

    /** html() implementation. */
    public function html() {
        // Open tag, append attributes, close the tag
        $str = "<form method='post'";
        $str .= $this->attr_html();
        $str .= ">";

        // Append form inputs, close element
        foreach($this->inputs as $input) {
            $str .= $input->html() . "</br>";
        }
        $str .= "</form>";

        // Add error message (if exists)
        if(isset($this->error)) {
            $str .= "<span style='color:red'>$this->error</span><br/>";
        }

        // Success
        return $str;
    }
}

/** A table: array of array of primitives. */
class Table extends Primitive {
    /** Rows (array of arrays). */
    private $rows;

    /** Initialize a table.
     * @param header table header
     */
    public function __construct($header = null) {
        parent::__construct();
        $this->rows = array();
        if ($header != null) {
            array_push($this->rows, $header);
        }
    }

    /** Append new row.
     * @param row an array of values
     */
    public function add($row) {
        array_push($this->rows, $row);
    }

    /** html() implementation. */
    public function html() {
        $str = '<table>';
        $tag = 'th';
        foreach($this->rows as $row) {
            $str .= '<tr>';
            foreach($row as $item) {
                $str .= "<$tag>";
                $str .= $item->html();
                $str .= "</$tag>";
            }
            $tag = 'td';
            $str .= '</tr>';
        }
        $str .= '</table>';
        return $str;
    }
}

class Listing extends Primitive {
    /** list type - ul, ol, il, etc. */
    private $type;
    /** list items */
    private $items;

    static public $list_types = array('ul', 'ol', 'dd', 'dt', 'dl');

    public function __construct($type = 'ul') {
        assert(in_array($type, Listing::$list_types));
        $this->type = $type;
        parent::__construct();
        $this->items = array();
    }

    public function add($item) {
        array_push($this->items, $item);
    }

    public function html() {
        $ret = "< $this->type>" . $this->attr_html() . '>';
        foreach ($this->childs as $primitive) {
            $ret .= '<li>' . $primitive->html() . '</li>';
        }
        $ret .= "<$this->type>";
        return $ret;
    }
}

?>
