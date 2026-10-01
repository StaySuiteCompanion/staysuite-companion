<?php
/**
 * Full-width page template for the VS homepage.
 *
 * Selected per page via Page Attributes → Template → VS Homepage.
 * Renders the block content without title, sidebar or container limits;
 * alignfull blocks break out to the viewport edges.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    ?>
    <main class="ssc-homepage-template">
        <?php the_content(); ?>
    </main>
    <?php
endwhile;

get_footer();
