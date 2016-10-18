<?php

/* * ****************************
 * Plugin Name: WP Post Import
 * Description: A plugin that helps to import the data's from a CSV file.
 * Version: 1.0.0
 * Author: mohd rihan ansari
 * Text Domain: wp-post-import
 * Domain Path: /languages
 * Plugin URI: http://naukri.com
 * Author URI: http://naukri.com
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

function action_post_csv_imp_admin_menu() {
    //add_menu_page('Import Post', 'Import Post', 'manage_options', 'import-post', 'import_directory_form', $icon_url, $position);
    add_submenu_page(
	    'edit.php?post_type=course', 'Import Post', /* page title */ 'Import Post', /* menu title */ 'manage_options', /* roles and capabiliyt needed */ 'import-post', 'import_directory_form' /* replace with your own function */
    );
}

add_action("admin_menu", "action_post_csv_imp_admin_menu");

function import_directory_form() {
    echo "<form enctype='multipart/form-data' method='post'>";
    //Upload File
    if (isset($_REQUEST['submit'])) {
	//Import uploaded file to Database
	$handle = fopen($_FILES['filename']['tmp_name'], "r");

	$row = 1;
	$post_types = get_post_types();

	echo "<table class='table table-striped table-bordered' cellspacing='0' widt44h='100%'>";
	echo "<thead><tr>"
	. "<th>Select Post Type</th>"
	. "<th><select name='post_type'>";
	foreach ($post_types as $k => $v) {
	    echo '<option value="' . $v . '">' . $v . '</option>';
	}

	echo "</select>"
	. "</th>"
	. "<th>Select Post Status</th>"
	. "<th><select name='post_status'>";

	$post_status = get_post_stati();
	foreach ($post_status as $k => $v) {
	    echo '<option value="' . $v . '">' . $v . '</option>';
	}

	$taxonomiArr = get_taxonomies();


	echo "</select>"
	. "</th><th><input type='submit' name='saveBtn' value='Start Mapping' class='btn btn-info'></th>"
	. "</tr><thead>";

	echo "</table>";
	echo '<table id="example" class="table table-responsive table-striped table-inverse table-striped table-bordered table-inverse" cellspacing="0" width="100%">';
	while (($data = fgetcsv($handle)) !== FALSE) {


	    if ($row == 1) {

		echo "<thead class='thead-inverse'><tr>";
		foreach ($data as $key => $value) {

		    echo "<th>"
		    . "<select required name='field[]'>"
		    . "<optgroup label='Select Field'>"
		    . "<option value='post__post_title'>Post Title</option>"
		    . "<option value='post__post_content'>Post Content</option>"
		    . "</optgroup>"
		    . "<optgroup label='Select Meta Field'>"
		    . "<option value='meta__url'>URL</option>"
		    . "</optgroup>"
		    . "<optgroup label='Select Taxonomy'>";
		    foreach ($taxonomiArr as $taxK => $taxV) {
			echo "<option value='taxonomy__" . $taxV . "'>" . $taxV . "</option>";
		    }


		    echo "</optgroup>"
		    . "<option>no mapping</option></select>"
		    . "</th>";
		}
		echo "</tr></thead>";
	    } else {
		echo "<tr>";

		foreach ($data as $key => $value) {
		    echo "<td>" . wp_trim_words(wp_strip_all_tags(trim($value)), "30", "...") . "<input type='hidden' name='data[post-" . $row . "][]' value='" . (wp_strip_all_tags(trim($value))) . "'></td>";
		}
		echo "</tr>";
	    }

	    $row++;
	}
	echo "</table>";

	fclose($handle);
	//view upload form
    } else {
	if (isset($_REQUEST['data'])) {
	    mapData();
	}
	
	if($_REQUEST['saveBtn']){
	    echo '<div class="alert alert-success">
  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  <strong>Success!</strong> Data import successfully...
</div>';
	}
	echo '<div class="container">
		<div class="row row-centered">
		    <div class="col-xs-10 col-centered text-center"><div class="item">
			<div class="content">
			    <h3>Upload Post and Postmeta...</h3>
			    <em>Upload new csv by browsing to file and clicking on Upload</em>
			    <input type="hidden" name="page" value="import-post"/>
			    <div class="form-group">
				<input type="file" name="filename" class="form-control" required>
			    <input type="submit" name="submit" value="Upload" class="btn btn-primary"></div>
			</div>
			</div>
		    </div>
		</div>
	    </div>';
    }
    echo "</form>";
}

$post = array();
$meta = array();
$taxonomy = array();

function mapData() {
    $count = 0;
    foreach ($_REQUEST['field'] as $key => $value) {
	$field_ex = explode('__', $value);
	if ($field_ex['0'] == "post") {
	    //get post field
	    $post[$field_ex['1']] = $key;
	    //$post = $field_ex['1'];
	} elseif ($field_ex['0'] == "meta") {
	    //get post meta field
	    $meta[$field_ex['1']] = $key;
	    //$meta = $field_ex['1'];
	} elseif ($field_ex['0'] == "taxonomy") {
	    //get taxonomy
	    $taxonomy[$field_ex['1']] = $key;
	    $taxono = $field_ex['1'];
	}
    }


    foreach ($_REQUEST['data'] as $key => $value) {
	// Create post object
	$my_post = array();
	foreach ($post as $key1 => $value1) {
	    $my_post[$key1] = wp_strip_all_tags($value[$value1]);
	}
	$my_post['post_status'] = $_REQUEST['post_status'];
	$my_post['post_type'] = $_REQUEST['post_type'];

	$post_id = wp_insert_post($my_post);
	//print_r($my_post);

	$my_post_meta = array();
	foreach ($meta as $key2 => $value2) {
	    $my_post_meta[$key2] = wp_strip_all_tags($value[$value2]);
	    update_post_meta($post_id, $key2, wp_strip_all_tags($value[$value2]));
	}
	$my_post_taxonomy = array();

	$tags = array();



	foreach ($taxonomy as $key3 => $value3) {
	    $my_post_taxonomy[$key3] = wp_strip_all_tags($value[$value3]);

	    $taxArr = explode(',', wp_strip_all_tags($value[$value3]));


	    if (count($taxArr) > 0) {
		foreach ($taxArr as $taxK) {
		    $tag = term_exists($taxK, $taxono);
		    $tag = $tag['term_id'];

		    if (!$tag) {
			$my_cat = array(
			    'cat_name' => wp_strip_all_tags($taxK),
			    'category_parent' => '',
			    'taxonomy' => $taxono
			);
			$tag = wp_insert_category($my_cat);
		    }
		    array_push($tags, $tag);
		}
	    } else {
		$tag = term_exists(wp_strip_all_tags($value[$value3], $taxono));

		$tag = $tag['term_id'];

		if (!$tag) {
		    $my_cat = array(
			'cat_name' => wp_strip_all_tags($value[$value3]),
			'category_parent' => '',
			'taxonomy' => $taxono
		    );
		    $tag = wp_insert_category($my_cat);
		}

		array_push($tags, $tag);
	    }

	    $taxonomy1 = $taxono;
	    wp_set_post_terms($post_id, $tags, $taxonomy1);
	}

	$count++;
    }
}

function course_touple($atts) {
    echo '<style>
    .course{float:left; width:100%;}
    .course_item {width:96%;border:2px solid #ccc;clear:both;float:left;padding:10px;margin-bottom:10px;border-left:5px solid #F7BE00 !important;}
    .course_left {float: left; width: 82%;}
    .course > h3 {color: #aa4500; font-size: 17px; font-weight: 600; padding: 10px 0;}
    .course_right {float: left;width: 18%; padding-top:15px;}
    .course .btn_style.btn-primary {color: #fff; background:#4D91F9;}
    .course_content {padding: 10px 0; color: #999; font-size: 14px;}
    .course_content > a.read_more {color: #6a98f1; padding: 0 0 0 5px;}
    .course_title h4 a {color: #000;}
    .btn_style {border: 1px solid transparent;border-radius:1px; cursor: pointer;display: inline-block;font-size: 14px;font-weight: 400;line-height: 1.42857;margin-bottom: 0; padding: 6px 20px; text-align: center; vertical-align: middle;white-space: nowrap; float: right;}
    .course .btn_style.btn-primary {background: #4d91f9 none repeat scroll 0 0;color: #fff;}
    .course-display-none{display:none;}
    .course-display-block{display:block;}
    .show_all, .hide_all{float:right; cursor:pointer; padding: 2px 4px 20px;}
    </style>';
    echo '<script>
    jQuery(document).ready(function(){
        jQuery(document).on("click",".show_all",function() {
        jQuery(".course_item").removeClass("course-display-none");
        jQuery(".show_all").html("Hide All");
        jQuery(".show_all").addClass("hide_all");
        jQuery(".show_all").removeClass("show_all");
        })
        jQuery(document).on("click",".hide_all",function() {
        jQuery(".course-disp").addClass("course-display-none");
        jQuery(".hide_all").html("Show All");
        jQuery(".hide_all").addClass("show_all");
        jQuery(".hide_all").removeClass("hide_all");
        });
    })
    </script>';
    $atts = shortcode_atts(array(
	'total-course' => '5',
	'visible-course' => '3',
	'content-length' => '15',
	'category' => '',
	'tag' => '',
	    ), $atts, 'course');

    $args = array(
	'posts_per_page' => $atts['total-course'],
	'offset' => 0,
	'course_taxonomy' => array_filter(explode(',', $atts['category'])),
	//'course_tags' => array_filter(explode(',', $atts['tag'])),
	'orderby' => 'date',
	'order' => 'DESC',
	'post_type' => 'course',
	'post_status' => 'publish',
	'suppress_filters' => true
    );

    $course_array = get_posts($args);

    if (count($course_array) > 0) {
	$html = '<div class="course">';
	$html .='<h3>Recommended Courses</h3>';
	foreach ($course_array as $key => $value) {
	    $course_meta = get_post_meta($value->ID);

	    $class = ($key > $atts['visible-course'] - 1 ? "course-display-none course-disp" : "course-display-block");

	    $html .= '<div class="course_item ' . $class . '">';
	    $html .= '<div class="course_left">';
	    $html .= '<div class="course_title"><h4><a href="' . $course_meta['url']['0'] . '">' . $value->post_title . '</a></h4></div>';
	    $html .= '<div class="course_content">' . wp_trim_words($value->post_content, $num_words = $atts['content-length'], $more = '...') . '</div>';
	    $html .= '</div>';
	    $html .= '<div class="course_right">';
	    $html .= '<div><a href="' . $course_meta['url']['0'] . '" class="btn_style btn-primary">Read More</a></div>';
	    $html .= '</div>';
	    $html .= '</div>';
	}

	if (($atts['total-course'] > $atts['visible-course']) && (count($course_array) > $atts['visible-course'])) {
	    $html .= '<div><a class="show_all">Show all</a></div>';
	}
	$html .= '</div>';

	return $html;
    }
}

add_shortcode('course', 'course_touple');


add_action('init', 'cptui_register_my_cpts_course');

function cptui_register_my_cpts_course() {
    $labels = array(
	"name" => __('Course', ''),
	"singular_name" => __('course', ''),
    );

    $args = array(
	"label" => __('Course', ''),
	"labels" => $labels,
	"description" => "",
	"public" => false,
	"show_ui" => true,
	"show_in_rest" => true,
	"rest_base" => "",
	"has_archive" => false,
	"show_in_menu" => true,
	"exclude_from_search" => false,
	"capability_type" => "post",
	"map_meta_cap" => true,
	"hierarchical" => false,
	"rewrite" => array("slug" => "course", "with_front" => true),
	"query_var" => true,
	"supports" => array("title", "editor", "thumbnail", "Course Tags", "custom-fields"),
    );
    register_post_type("course", $args);

// End of cptui_register_my_cpts_course()
}

add_action('init', 'cptui_register_my_taxes_course_taxonomy');

function cptui_register_my_taxes_course_taxonomy() {
    $labels = array(
	"name" => __('Course Category', ''),
	"singular_name" => __('Course Category', ''),
    );

    $args = array(
	"label" => __('Course Category', ''),
	"labels" => $labels,
	"public" => true,
	"hierarchical" => true,
	"label" => "Course Category",
	"show_ui" => true,
	"query_var" => true,
	"rewrite" => array('slug' => 'course_taxonomy', 'with_front' => true),
	"show_admin_column" => true,
	"show_in_rest" => false,
	"rest_base" => "",
	"show_in_quick_edit" => true,
    );
    register_taxonomy("course_taxonomy", array("course"), $args);

// End cptui_register_my_taxes_course_taxonomy()
}

/**
 * Enqueue a script with jQuery as a dependency.
 */
function load_dependency() {
    $page = $_REQUEST['page'];
    if ($page == "import-post") {
	wp_enqueue_style('bootstrap', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css');
	wp_enqueue_style('bootstrap-data-table', plugin_dir_url(__FILE__) . 'css/dataTables.bootstrap.min.css');

	wp_enqueue_script('bootstrap-data-table', plugin_dir_url(__FILE__) . 'js/jquery.dataTables.min.js', array('jquery'));
	wp_enqueue_script('bootstrap-data-table-bootstrap', plugin_dir_url(__FILE__) . 'js/dataTables.bootstrap.min.js', array('jquery'));
	wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'js/custom-script.js', array('jquery'));
    }
}

add_action('admin_enqueue_scripts', 'load_dependency');
