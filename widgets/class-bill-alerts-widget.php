<?php
/**
 * Bill Alerts Widget
 */
class Bill_Alerts_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'bill_alerts_widget',
            __('Bill Alerts', 'financial-email-client'),
            array('description' => __('Displays alerts for upcoming bills from emails.', 'financial-email-client'))
        );
    }
    
    /**
     * Widget front-end display
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        echo '<div class="fec-widget-content">';
        echo '<p>' . __('Bill alerts will be displayed here.', 'financial-email-client') . '</p>';
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget admin form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Bill Alerts', 'financial-email-client');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'financial-email-client'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    /**
     * Widget update
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        
        return $instance;
    }
}