<?php

/* Plugin name: Преимущества компании */

add_action('init', 'benefits_main');

function benefits_main()
{
    $taxLabels = [
        'name'              => 'Категории преимуществ',
        'singular_name'     => 'Категория преимущества',
        'search_items'      => 'Поиск категорий',
        'all_items'         => 'Все категории',
        'view_item '        => 'Просмотреть категории',
        'parent_item'       => 'Родительская категория',
        'parent_item_colon' => 'Родительская категория:',
        'edit_item'         => 'Редактировать категорию',
        'update_item'       => 'Обновить категорию',
        'add_new_item'      => 'Добавить категорию',
        'new_item_name'     => 'Новая категория',
        'menu_name'         => 'Категории преимуществ',
        'back_to_items'     => '← Вернуться к категориям',
    ];

    $taxArgs = [
        'public' => true,
        'labels' => $taxLabels,
        'hierarchical' => true,
    ];

    register_taxonomy('benefits-category', ['benefits'], $taxArgs);


    $labels = array(
        'name' => 'Преимущества',
        'singular_name' => 'Преимущество',
        'add_new' => 'Добавить преимущество',
        'add_new_item' => 'Добавить преимущество',
        'edit_item' => 'Редактировать преимущество',
        'new_item' => 'Новое преимущество',
        'all_items' => 'Все преимущества',
        'search_items' => 'Искать преимущества',
        'not_found' =>  'Преимуществ по заданным критериям не найдено.',
        'not_found_in_trash' => 'В корзине нет преимуществ.',
        'menu_name' => 'Преимущества'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-megaphone',
        'menu_position' => 3,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions')
    );

    register_post_type('benefits', $args);
}

add_action('add_meta_boxes', 'benefits_add_custom_box');

function benefits_add_custom_box()
{
    $screens = ['benefits'];
    foreach ($screens as $screen) {
        add_meta_box(
            'benefits_metabox',
            'Преимущество',
            'benefits_custom_box_html',
            $screen,
            'normal',
            'high'
        );
    }
}

add_action('post_edit_form_tag', 'benefits_post_edit_form_tag');

function benefits_post_edit_form_tag($post)
{
    if ($post->post_type === 'benefits') {
        echo ' enctype="multipart/form-data"';
    }
}

function benefits_custom_box_html($post)
{
    // сначала получаем значения этих полей
    $benefit_name = get_post_meta($post->ID, 'benefit_name', true);
    $benefit_description = get_post_meta($post->ID, 'benefit_description', true);

    wp_nonce_field('gavrilovegor519-benefits-' . $post->ID, '_truenonce');

?>
    <label for="image_box">Фото преимущества</label>
    <input type="file" id="image_box" name="image_box" value="">

    <br />

    <label for="name">Имя преимущества</label>
    <input type="text" value="<?= esc_attr($benefit_name); ?>" id="name" name="name" class="regular-text">

    <br />

    <label for="description">Описание преимущества</label>
    <input type="text" value="<?= esc_attr($benefit_description); ?>" id="description" name="description" class="regular-text">
<?php
}

add_action('save_post', 'true_save_meta_benefits', 10, 2);

function true_save_meta_benefits($post_id, $post)
{

    // проверка одноразовых полей
    if (!isset($_POST['_truenonce']) || !wp_verify_nonce($_POST['_truenonce'], 'gavrilovegor519-benefits-' . $post->ID)) {
        return $post_id;
    }

    // проверяем, может ли текущий юзер редактировать пост
    $post_type = get_post_type_object($post->post_type);

    if (!current_user_can($post_type->cap->edit_post, $post_id)) {
        return $post_id;
    }

    // ничего не делаем для автосохранений
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // проверяем тип записи
    if (!in_array($post->post_type, array('benefits'))) {
        return $post_id;
    }

    if (!empty($_FILES['image_box']['name'])) {
        $supported_types = array('image/jpeg', 'image/png', 'image/webp');

        // Получаем тип файла
        $arr_file_type = wp_check_filetype(basename($_FILES['image_box']['name']));
        $uploaded_type = $arr_file_type['type'];

        // Проверяем тип файла на совместимость
        if (in_array($uploaded_type, $supported_types)) {
            $upload = wp_upload_bits($_FILES['image_box']['name'], null, file_get_contents($_FILES['image_box']['tmp_name']));

            if (isset($upload['error']) && $upload['error'] != 0) {
                error_log($message, 3, $pluginlog);
            } else {
                update_post_meta($post_id, 'benefits_photo', $upload['url']);
            }
        } else {
            wp_die("The file type that you've uploaded is not a JPEG/PNG/WebP.");
        }
    }

    if (isset($_POST['name'])) {
        update_post_meta($post_id, 'benefit_name', sanitize_text_field($_POST['name']));
    } else {
        delete_post_meta($post_id, 'benefit_name');
    }
    if (isset($_POST['description'])) {
        update_post_meta($post_id, 'benefit_description', sanitize_text_field($_POST['description']));
    } else {
        delete_post_meta($post_id, 'benefit_description');
    }

    return $post_id;
}

add_shortcode('benefits_list', 'benefits_list_shortcode');

function benefits_list_shortcode()
{
    $args = array(
        'post_type' => 'benefits',
        'posts_per_page' => -1, // Выводим все записи
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $output = '<div class="benefits-list">';
        while ($query->have_posts()) {
            $query->the_post();

            $name = get_post_meta(get_the_ID(), 'benefit_name', true);
            $photo = get_post_meta(get_the_ID(), 'benefits_photo', true);
            $description = get_post_meta(get_the_ID(), 'benefit_description', true);

            $output .= '<div class="benefit-item">';
            $output .= '<h3>' . $name . '</h3>';
            $output .= '<img src="' . $photo . '" alt="' . $name . '" width="300px" />';
            if (!empty($description)) {
                $output .= '<p>' . esc_html($description) . '</p>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    } else {
        return 'Нет преимуществ';
    }
}

add_shortcode('benefits_categories', 'benefits_taxonomies_shortcode');

function benefits_taxonomies_shortcode()
{
    $taxonomy = 'benefits-category';
        
    // Получаем список разделов таксономии
    $terms = get_terms($taxonomy);
        
    // Создаём HTML для шорткода
    $html = '<ul>';
    foreach ($terms as $term) {
        $html .= '<li><a href="' . get_term_link($term) . '">' . $term->name . '</a></li>';
    }
    $html .= '</ul>';
        
    // Возвращаем HTML
    return $html;
}

class benefits_taxonomies_widget extends WP_Widget {
    public function __construct() {
        $widget_options = array(
            'classname' => 'benefits_taxonomies_widget',
            'description' => 'Категории преимуществ',
        );
        parent::__construct( 'benefits_taxonomies_widget', 'Категории преимуществ', $widget_options );
    }
    
    public function widget( $args, $instance ) {
        $taxonomy = 'benefits-category';
        $title = $instance[ 'title' ];
        
        // Получаем список разделов таксономии
        $terms = get_terms($taxonomy);
        
        // Создаём HTML для виджета
        $html = $args['before_widget'] . $args['before_title'] . $title . $args['after_title'] . '<ul>';
        foreach ($terms as $term) {
            $html .= '<li><a href="' . get_term_link($term) . '">' . $term->name . '</a></li>';
        }
        $html .= '</ul>' . $args['after_widget'];
        
        // Возвращаем HTML
        echo $html;
    }
    
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : ''; ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
        <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
        </p><?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
        return $instance;
    }
}

class benefits_list_widget extends WP_Widget {
    public function __construct() {
        $widget_options = array(
            'classname' => 'benefits_list_widget',
            'description' => 'Список преимуществ',
        );
        parent::__construct( 'benefits_list_widget', 'Список преимуществ', $widget_options );
    }
    
    public function widget( $args, $instance ) {
        $title = $instance[ 'title' ];
        
        $args1 = array(
            'post_type' => 'benefits',
            'posts_per_page' => 5, // Выводим 5 записей
        );
    
        $query = new WP_Query($args1);
    
        $output = $args['before_widget'] . $args['before_title'] . $title . $args['after_title'];
        if ($query->have_posts()) {
            $output .= '<ul>';
            while ($query->have_posts()) {
                $query->the_post();
    
                $name = get_post_meta(get_the_ID(), 'benefit_name', true);
                
                $output .= '<li><a href="' . get_permalink() . '">' . $name . '</a></li>';
            }
            wp_reset_postdata();
            $output .= '</ul>' . $args['after_widget'];
            echo $output;
        } else {
            $output .= '<p>Нет преимуществ<p>' . $args['after_widget'];
            echo $output;
        }
    }
    
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : ''; ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
        <input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
        </p><?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
        return $instance;
    }
}

function benefits_register_widget() {
    register_widget( 'benefits_taxonomies_widget' );
    register_widget( 'benefits_list_widget' );
}

add_action( 'widgets_init', 'benefits_register_widget' );
