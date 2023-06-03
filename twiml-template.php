<?php
/**
 * Template Name: TwiML Template
 */
?>
<?php
if (have_posts()) {
    while (have_posts()) {
        the_post();
        the_content();
    }
}
?>
