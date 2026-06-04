<?php
/**
 * Register Custom Post Types and Taxonomies
 *
 * @link       https://example.com
 * @since      1.0.0
 * @package    Festival_Winners_Showcase
 * @subpackage Festival_Winners_Showcase/includes
 */

class FWS_Post_Types {

    /**
     * Register Custom Post Type: winner_entry
     */
    public function register_post_types() {

        $labels = array(
            'name'                  => _x( 'برندگان', 'Post Type General Name', 'festival-winners-showcase' ),
            'singular_name'         => _x( 'برنده', 'Post Type Singular Name', 'festival-winners-showcase' ),
            'menu_name'             => __( '🏆 برندگان جشنواره', 'festival-winners-showcase' ),
            'name_admin_bar'        => __( 'برنده', 'festival-winners-showcase' ),
            'archives'              => __( 'آرشیو برندگان', 'festival-winners-showcase' ),
            'attributes'            => __( 'ویژگی‌های برنده', 'festival-winners-showcase' ),
            'parent_item_colon'     => __( 'برنده والد:', 'festival-winners-showcase' ),
            'all_items'             => __( 'همه برندگان', 'festival-winners-showcase' ),
            'add_new_item'          => __( 'افزودن برنده جدید', 'festival-winners-showcase' ),
            'add_new'               => __( 'افزودن جدید', 'festival-winners-showcase' ),
            'new_item'              => __( 'برنده جدید', 'festival-winners-showcase' ),
            'edit_item'             => __( 'ویرایش برنده', 'festival-winners-showcase' ),
            'update_item'           => __( 'بروزرسانی برنده', 'festival-winners-showcase' ),
            'view_item'             => __( 'مشاهده برنده', 'festival-winners-showcase' ),
            'view_items'            => __( 'مشاهده برندگان', 'festival-winners-showcase' ),
            'search_items'          => __( 'جستجوی برنده', 'festival-winners-showcase' ),
            'not_found'             => __( 'یافت نشد', 'festival-winners-showcase' ),
            'not_found_in_trash'    => __( 'در زباله‌دان یافت نشد', 'festival-winners-showcase' ),
            'featured_image'        => __( 'تصویر اصلی', 'festival-winners-showcase' ),
            'set_featured_image'    => __( 'تنظیم تصویر اصلی', 'festival-winners-showcase' ),
            'remove_featured_image' => __( 'حذف تصویر اصلی', 'festival-winners-showcase' ),
            'use_featured_image'    => __( 'استفاده به عنوان تصویر اصلی', 'festival-winners-showcase' ),
            'insert_into_item'      => __( 'درج در برنده', 'festival-winners-showcase' ),
            'uploaded_to_this_item' => __( 'آپلود شده در این برنده', 'festival-winners-showcase' ),
            'items_list'            => __( 'لیست برندگان', 'festival-winners-showcase' ),
            'items_list_navigation' => __( 'ناوبری لیست برندگان', 'festival-winners-showcase' ),
            'filter_items_list'     => __( 'فیلتر لیست برندگان', 'festival-winners-showcase' ),
        );
        $args = array(
            'label'                 => __( 'برنده', 'festival-winners-showcase' ),
            'description'           => __( 'برندگان جشنواره عکاسی', 'festival-winners-showcase' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail' ),
            'taxonomies'            => array( 'winner_category' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-awards',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        register_post_type( 'winner_entry', $args );

    }

    /**
     * Register Custom Taxonomy: winner_category
     */
    public function register_taxonomies() {

        $labels = array(
            'name'                       => _x( 'دسته‌بندی‌های برندگان', 'Taxonomy General Name', 'festival-winners-showcase' ),
            'singular_name'              => _x( 'دسته‌بندی برنده', 'Taxonomy Singular Name', 'festival-winners-showcase' ),
            'menu_name'                  => __( 'دسته‌بندی‌ها', 'festival-winners-showcase' ),
            'all_items'                  => __( 'همه دسته‌بندی‌ها', 'festival-winners-showcase' ),
            'parent_item'                => __( 'دسته‌بندی والد', 'festival-winners-showcase' ),
            'parent_item_colon'          => __( 'دسته‌بندی والد:', 'festival-winners-showcase' ),
            'new_item_name'              => __( 'نام دسته‌بندی جدید', 'festival-winners-showcase' ),
            'add_new_item'               => __( 'افزودن دسته‌بندی جدید', 'festival-winners-showcase' ),
            'edit_item'                  => __( 'ویرایش دسته‌بندی', 'festival-winners-showcase' ),
            'update_item'                => __( 'بروزرسانی دسته‌بندی', 'festival-winners-showcase' ),
            'view_item'                  => __( 'مشاهده دسته‌بندی', 'festival-winners-showcase' ),
            'separate_items_with_commas' => __( 'جدا کردن دسته‌ها با کاما', 'festival-winners-showcase' ),
            'add_or_remove_items'        => __( 'افزودن یا حذف دسته‌بندی‌ها', 'festival-winners-showcase' ),
            'choose_from_most_used'      => __( 'انتخاب از پراستفاده‌ترین‌ها', 'festival-winners-showcase' ),
            'popular_items'              => __( 'دسته‌بندی‌های محبوب', 'festival-winners-showcase' ),
            'search_items'               => __( 'جستجوی دسته‌بندی‌ها', 'festival-winners-showcase' ),
            'not_found'                  => __( 'یافت نشد', 'festival-winners-showcase' ),
            'no_terms'                   => __( 'بدون دسته‌بندی', 'festival-winners-showcase' ),
            'items_list'                 => __( 'لیست دسته‌بندی‌ها', 'festival-winners-showcase' ),
            'items_list_navigation'      => __( 'ناوبری لیست دسته‌بندی‌ها', 'festival-winners-showcase' ),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
        );
        register_taxonomy( 'winner_category', array( 'winner_entry' ), $args );

    }

}
