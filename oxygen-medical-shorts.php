<?php
/**
 * Plugin Name: Oxygen Medical Shorts
 * Plugin URI:  https://up2digital.hu
 * Description: YouTube Shorts kezelése és megjelenítése carouselban. Saját CPT-vel és WP admin színbeállítóval. Shortcode: [om_shorts]
 * Version:     1.3.0
 * Author:      UP2Digital
 * Author URI:  https://up2digital.hu
 * Text Domain: oxygen-shorts
 */

if (!defined('ABSPATH')) { exit; }

class OM_Shorts_Plugin {

    const CPT    = 'om_short';
    const META   = '_om_video_url';
    const NONCE  = 'om_short_nonce';
    const OPTION = 'om_shorts_options';

    public function __construct() {
        add_action('init',                                          [$this, 'register_post_type']);
        add_action('add_meta_boxes',                                [$this, 'add_meta_box']);
        add_action('save_post_' . self::CPT,                        [$this, 'save_meta']);
        add_filter('manage_' . self::CPT . '_posts_columns',        [$this, 'admin_columns']);
        add_action('manage_' . self::CPT . '_posts_custom_column',  [$this, 'admin_columns_content'], 10, 2);
        add_shortcode('om_shorts',                                  [$this, 'shortcode']);
        add_action('admin_menu',                                    [$this, 'settings_menu']);
        add_action('admin_init',                                    [$this, 'register_settings']);
        add_action('admin_enqueue_scripts',                         [$this, 'admin_assets']);
    }

    /* =====================================================================
     * CUSTOM POST TYPE
     * ================================================================== */
    public function register_post_type() {
        register_post_type(self::CPT, [
            'labels' => [
                'name'               => 'Shorts videók',
                'singular_name'      => 'Short videó',
                'add_new'            => 'Új videó',
                'add_new_item'       => 'Új videó hozzáadása',
                'edit_item'          => 'Videó szerkesztése',
                'new_item'           => 'Új videó',
                'all_items'          => 'Összes videó',
                'search_items'       => 'Videó keresése',
                'not_found'          => 'Nincs találat.',
                'not_found_in_trash' => 'Nincs a kukában.',
                'menu_name'          => 'Shorts videók',
            ],
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_position' => 25,
            'menu_icon'     => 'dashicons-video-alt3',
            'supports'      => ['title', 'excerpt', 'thumbnail', 'page-attributes'],
            'has_archive'   => false,
            'rewrite'       => false,
        ]);
    }

    /* =====================================================================
     * META BOX (Video URL)
     * ================================================================== */
    public function add_meta_box() {
        add_meta_box('om_short_video_url', 'YouTube videó URL',
            [$this, 'render_meta_box'], self::CPT, 'normal', 'high');
    }

    public function render_meta_box($post) {
        $video_url = get_post_meta($post->ID, self::META, true);
        wp_nonce_field(self::NONCE, self::NONCE);
        ?>
        <p>
            <label for="om_video_url"><strong>YouTube videó URL:</strong></label><br>
            <input type="url" id="om_video_url" name="om_video_url"
                   value="<?php echo esc_attr($video_url); ?>"
                   style="width:100%;padding:8px;font-size:14px;"
                   placeholder="https://www.youtube.com/shorts/VIDEO_ID">
        </p>
        <p style="color:#666;font-size:13px;margin-top:8px;line-height:1.6;">
            Bemásolható formátumok: <code>/shorts/</code>, <code>/watch?v=</code>, <code>youtu.be/</code><br><br>
            <strong>Cím</strong> = a kártyán nagybetűs cím<br>
            <strong>Kivonat</strong> (jobb oldalon, opcionális mező — ha nem látszik: Képernyő beállítások → Kivonat pipa) = a leírás a cím alatt<br>
            <strong>Kiemelt kép</strong> (jobb oldalon, opcionális) = ha üres, a YouTube auto-borítóját használjuk
        </p>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST[self::NONCE]) || !wp_verify_nonce($_POST[self::NONCE], self::NONCE)) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (isset($_POST['om_video_url'])) {
            update_post_meta($post_id, self::META, esc_url_raw($_POST['om_video_url']));
        }
    }

    /* =====================================================================
     * ADMIN OSZLOPOK
     * ================================================================== */
    public function admin_columns($cols) {
        $new = [];
        foreach ($cols as $key => $val) {
            if ($key === 'title') {
                $new['om_thumb'] = 'Borító';
            }
            $new[$key] = $val;
            if ($key === 'title') {
                $new['om_url'] = 'Video URL';
            }
        }
        return $new;
    }

    public function admin_columns_content($col, $post_id) {
        if ($col === 'om_thumb') {
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [50, 70]);
            } else {
                $vid = $this->extract_video_id(get_post_meta($post_id, self::META, true));
                if ($vid) {
                    echo '<img src="https://i.ytimg.com/vi/' . esc_attr($vid) . '/default.jpg" style="width:50px;height:70px;object-fit:cover;border-radius:4px;">';
                } else {
                    echo '—';
                }
            }
        }
        if ($col === 'om_url') {
            $url = get_post_meta($post_id, self::META, true);
            echo $url
                ? '<a href="' . esc_url($url) . '" target="_blank">Megnyitás ↛</a>'
                : '<span style="color:#c00">— hiányzik —</span>';
        }
    }

    private function extract_video_id($url) {
        if (!$url) return '';
        if (preg_match('#(?:youtube\.com/(?:shorts/|watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return $m[1];
        }
        return '';
    }

    /* =====================================================================
     * BEÁLLÍTÁSOK
     * ================================================================== */
    public function get_options() {
        $defaults = [
            'accent_color'        => '#0b5f5b',
            'title_color'         => '#0b5f5b',
            'text_color'          => '#555555',
            'card_bg'             => '#ffffff',
            'section_title_color' => '#1a1a1a',
            'show_cta_badge'      => 1,
            'cta_badge_text'      => 'Megnézem',
            'uppercase_title'     => 1,
            'autoplay'            => 1,
            'autoplay_delay'      => 3000,
            'loop'                => 1,
            'pause_on_hover'      => 1,
        ];
        $opts = get_option(self::OPTION, []);
        return is_array($opts) ? array_merge($defaults, $opts) : $defaults;
    }

    public function settings_menu() {
        add_submenu_page(
            'edit.php?post_type=' . self::CPT,
            'Megjelenés beállítások',
            'Megjelenés',
            'manage_options',
            'om-shorts-settings',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting('om_shorts_settings', self::OPTION, [$this, 'sanitize_options']);
    }

    public function sanitize_options($input) {
        $clean = [];
        $clean['accent_color']        = sanitize_hex_color($input['accent_color'] ?? '#0b5f5b') ?: '#0b5f5b';
        $clean['title_color']         = sanitize_hex_color($input['title_color'] ?? '#0b5f5b') ?: '#0b5f5b';
        $clean['text_color']          = sanitize_hex_color($input['text_color'] ?? '#555555') ?: '#555555';
        $clean['card_bg']             = sanitize_hex_color($input['card_bg'] ?? '#ffffff') ?: '#ffffff';
        $clean['section_title_color'] = sanitize_hex_color($input['section_title_color'] ?? '#1a1a1a') ?: '#1a1a1a';
        $clean['show_cta_badge']      = !empty($input['show_cta_badge']) ? 1 : 0;
        $clean['cta_badge_text']      = sanitize_text_field($input['cta_badge_text'] ?? 'Megnézem');
        $clean['uppercase_title']     = !empty($input['uppercase_title']) ? 1 : 0;
        $clean['autoplay']            = !empty($input['autoplay']) ? 1 : 0;
        $clean['autoplay_delay']      = max(1000, intval($input['autoplay_delay'] ?? 3000));
        $clean['loop']                = !empty($input['loop']) ? 1 : 0;
        $clean['pause_on_hover']      = !empty($input['pause_on_hover']) ? 1 : 0;
        return $clean;
    }

    public function admin_assets($hook) {
        if (strpos($hook, 'om-shorts-settings') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_add_inline_script('wp-color-picker',
                'jQuery(function($){$(".om-color-picker").wpColorPicker();});');
        }
    }

    public function settings_page() {
        $opts = $this->get_options();
        $opt  = self::OPTION;
        ?>
        <div class="wrap">
            <h1>Shorts videók — Megjelenés</h1>
            <p style="max-width:800px">
                A színek a kezdőlapi videó-carouselen érvényesülnek. Adott shortcode-on belül felülírható
                az <code>accent="#xxxxxx"</code> attribútummal (pl. ha eltérő színt akarsz egy másik oldalon).
            </p>

            <form method="post" action="options.php">
                <?php settings_fields('om_shorts_settings'); ?>

                <h2 style="margin-top:30px">Színek</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Kiemelt szín (badge, navigáció, vonal)</th>
                        <td><input type="text" class="om-color-picker" name="<?php echo $opt; ?>[accent_color]"
                                   value="<?php echo esc_attr($opts['accent_color']); ?>" data-default-color="#1F5961"></td>
                    </tr>
                    <tr>
                        <th scope="row">Cím színe</th>
                        <td><input type="text" class="om-color-picker" name="<?php echo $opt; ?>[title_color]"
                                   value="<?php echo esc_attr($opts['title_color']); ?>" data-default-color="#1a1a1a"></td>
                    </tr>
                    <tr>
                        <th scope="row">Leírás szövegszín</th>
                        <td><input type="text" class="om-color-picker" name="<?php echo $opt; ?>[text_color]"
                                   value="<?php echo esc_attr($opts['text_color']); ?>" data-default-color="#555555"></td>
                    </tr>
                    <tr>
                        <th scope="row">Kártya háttérszín</th>
                        <td><input type="text" class="om-color-picker" name="<?php echo $opt; ?>[card_bg]"
                                   value="<?php echo esc_attr($opts['card_bg']); ?>" data-default-color="#ffffff"></td>
                    </tr>
                    <tr>
                        <th scope="row">Szekció cím színe</th>
                        <td><input type="text" class="om-color-picker" name="<?php echo $opt; ?>[section_title_color]"
                                   value="<?php echo esc_attr($opts['section_title_color']); ?>" data-default-color="#1a1a1a"></td>
                    </tr>
                </table>

                <h2 style="margin-top:30px">Kártya megjelenés</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Cím nagybetűs?</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $opt; ?>[uppercase_title]" value="1"
                                       <?php checked($opts['uppercase_title'], 1); ?>>
                                Igen — UPPERCASE megjelenítés (mint az akciós kártyáknál)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">CTA badge a kártya alján?</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $opt; ?>[show_cta_badge]" value="1"
                                       <?php checked($opts['show_cta_badge'], 1); ?>>
                                Igen — kis színes gomb a kártya alján (mint az árcímke az akciós kártyán)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Badge szövege</th>
                        <td><input type="text" name="<?php echo $opt; ?>[cta_badge_text]"
                                   value="<?php echo esc_attr($opts['cta_badge_text']); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2 style="margin-top:30px">Automatikus lapozás</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Autoplay bekapcsolva?</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $opt; ?>[autoplay]" value="1"
                                       <?php checked($opts['autoplay'], 1); ?>>
                                Igen — a videók automatikusan lapozódnak balra
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lapozási időköz (ms)</th>
                        <td>
                            <input type="number" min="1000" step="100" name="<?php echo $opt; ?>[autoplay_delay]"
                                   value="<?php echo esc_attr($opts['autoplay_delay']); ?>" class="small-text">
                            <span style="color:#666">ms (3000 = 3 másodperc)</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Végtelenített lapozás?</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $opt; ?>[loop]" value="1"
                                       <?php checked($opts['loop'], 1); ?>>
                                Igen — a végén visszalapoz az elejére (loop)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Megállás egérrel?</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $opt; ?>[pause_on_hover]" value="1"
                                       <?php checked($opts['pause_on_hover'], 1); ?>>
                                Igen — amikor rávisz az egérrel, áll az autoplay
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Mentés'); ?>
            </form>

            <h2 style="margin-top:40px">Használat</h2>
            <p>Tedd a következő shortcode-ot egy Divi <strong>Code modulba</strong> a kezdőlapon (vagy bárhol):</p>
            <pre style="background:#fff;padding:15px;border-left:4px solid <?php echo esc_attr($opts['accent_color']); ?>;font-size:14px">[om_shorts limit="6" title="Egy perc Oxygen Medical" intro="Hírek a központunkból, tippek a megelőzésről, szakorvosi válaszok és bepillantás kezeléseinkbe — minden, amit érdemes tudni rólunk és az egészségéről, egy percnél is rövidebb videókban."]</pre>

            <h3>Shortcode paraméterek</h3>
            <table class="widefat striped" style="max-width:800px">
                <thead><tr><th>Paraméter</th><th>Alapért.</th><th>Leírás</th></tr></thead>
                <tbody>
                    <tr><td><code>limit</code></td><td>6</td><td>hány videót húzzon le</td></tr>
                    <tr><td><code>title</code></td><td>—</td><td>szekció címe (H2)</td></tr>
                    <tr><td><code>intro</code></td><td>—</td><td>bevezető szöveg a cím alatt</td></tr>
                    <tr><td><code>desktop</code></td><td>4</td><td>≥1200px: hány videó látszik egyszerre</td></tr>
                    <tr><td><code>tablet</code></td><td>3</td><td>≥900px: hány videó</td></tr>
                    <tr><td><code>mobile</code></td><td>1.2</td><td>&lt;640px: hány videó</td></tr>
                    <tr><td><code>orderby</code></td><td>date</td><td><code>date</code>, <code>menu_order</code>, <code>rand</code></td></tr>
                    <tr><td><code>order</code></td><td>DESC</td><td><code>DESC</code> = legújabb előre</td></tr>
                    <tr><td><code>accent</code></td><td>—</td><td>kiemelt szín felülírása erre az egy shortcode-ra (pl. <code>accent="#c0392b"</code>)</td></tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* =====================================================================
     * SHORTCODE
     * ================================================================== */
    public function shortcode($atts) {
        $opts = $this->get_options();
        $atts = shortcode_atts([
            'limit'          => 6,
            'title'          => '',
            'intro'          => '',
            'desktop'        => 4,
            'tablet'         => 3,
            'mobile'         => 1.2,
            'accent'         => '',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'autoplay'       => '',
            'autoplay_delay' => '',
            'loop'           => '',
        ], $atts, 'om_shorts');

        if (!empty($atts['accent'])) {
            $opts['accent_color'] = $atts['accent'];
        }
        if ($atts['autoplay'] !== '')       { $opts['autoplay']       = intval($atts['autoplay']); }
        if ($atts['autoplay_delay'] !== '') { $opts['autoplay_delay'] = max(1000, intval($atts['autoplay_delay'])); }
        if ($atts['loop'] !== '')           { $opts['loop']           = intval($atts['loop']); }

        $query = new WP_Query([
            'post_type'      => self::CPT,
            'posts_per_page' => intval($atts['limit']),
            'post_status'    => 'publish',
            'orderby'        => sanitize_text_field($atts['orderby']),
            'order'          => sanitize_text_field($atts['order']),
            'no_found_rows'  => true,
        ]);

        if (!$query->have_posts()) {
            return '<!-- om_shorts: nincs publikált videó -->';
        }

        $style_vars = sprintf(
            '--om-accent:%s;--om-title:%s;--om-text:%s;--om-card-bg:%s;--om-section-title:%s;',
            esc_attr($opts['accent_color']),
            esc_attr($opts['title_color']),
            esc_attr($opts['text_color']),
            esc_attr($opts['card_bg']),
            esc_attr($opts['section_title_color'])
        );

        ob_start(); ?>
        <div class="om-shorts-wrap" style="<?php echo $style_vars; ?>">
            <?php if (!empty($atts['title'])): ?>
                <h2 class="om-shorts-section-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($atts['intro'])): ?>
                <p class="om-shorts-intro"><?php echo wp_kses_post($atts['intro']); ?></p>
            <?php endif; ?>

            <div class="om-shorts-carousel-outer">
                <div class="swiper om-shorts-swiper"
                     data-desktop="<?php echo esc_attr($atts['desktop']); ?>"
                     data-tablet="<?php echo esc_attr($atts['tablet']); ?>"
                     data-mobile="<?php echo esc_attr($atts['mobile']); ?>"
                     data-autoplay="<?php echo !empty($opts['autoplay']) ? '1' : '0'; ?>"
                     data-autoplay-delay="<?php echo esc_attr($opts['autoplay_delay']); ?>"
                     data-loop="<?php echo !empty($opts['loop']) ? '1' : '0'; ?>"
                     data-pause-hover="<?php echo !empty($opts['pause_on_hover']) ? '1' : '0'; ?>">
                    <div class="swiper-wrapper">
                        <?php while ($query->have_posts()): $query->the_post();
                            $vid = $this->extract_video_id(get_post_meta(get_the_ID(), self::META, true));
                            if (!$vid) continue;
                            $thumb = has_post_thumbnail()
                                ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large')
                                : 'https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg';
                            $excerpt = has_excerpt() ? get_the_excerpt() : '';
                        ?>
                        <div class="swiper-slide">
                            <div class="om-short<?php echo $opts['uppercase_title'] ? ' om-uppercase' : ''; ?>" data-video="<?php echo esc_attr($vid); ?>">
                                <div class="om-thumb" role="button" tabindex="0" aria-label="Videó lejátszása: <?php the_title_attribute(); ?>">
                                    <img loading="lazy" src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
                                    <span class="om-play" aria-hidden="true">
                                        <svg viewBox="0 0 68 48"><path d="M66.52 7.74A8 8 0 0 0 60.86 2.1C55.84.5 34 .5 34 .5s-21.84 0-26.86 1.6A8 8 0 0 0 1.48 7.74C0 12.79 0 24 0 24s0 11.21 1.48 16.26A8 8 0 0 0 7.14 45.9C12.16 47.5 34 47.5 34 47.5s21.84 0 26.86-1.6a8 8 0 0 0 5.66-5.64C68 35.21 68 24 68 24s0-11.21-1.48-16.26z" fill="#f00"/><path d="M27 34l18-10-18-10z" fill="#fff"/></svg>
                                    </span>
                                </div>
                                <div class="om-card-body">
                                    <h3 class="om-short-title"><?php the_title(); ?></h3>
                                    <?php if ($excerpt): ?>
                                        <p class="om-short-desc"><?php echo esc_html($excerpt); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($opts['show_cta_badge'])): ?>
                                <div class="om-card-footer">
                                    <button type="button" class="om-cta-badge">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        <span><?php echo esc_html($opts['cta_badge_text']); ?></span>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                <button type="button" class="om-shorts-nav om-shorts-prev" data-icon="4" aria-label="Előző"></button>
                <button type="button" class="om-shorts-nav om-shorts-next" data-icon="5" aria-label="Következő"></button>
                <div class="swiper-pagination om-pag"></div>
            </div>
        </div>

        <?php $this->print_assets(); ?>
        <?php
        return ob_get_clean();
    }

    /* =====================================================================
     * CSS + JS (csak egyszer oldalanként)
     * ================================================================== */
    private function print_assets() {
        static $printed = false;
        if ($printed) return;
        $printed = true;
        ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
        <style>
        /* ---------- WRAPPER ---------- */
        .om-shorts-wrap{
            width:100%;
            margin:0 auto;
            padding:30px 0 70px;
            position:relative;
            box-sizing:border-box;
        }

        /* ---------- SECTION TITLE & INTRO ---------- */
        .om-shorts-section-title{
            text-align:center;
            font-size:36px;
            margin:0 auto 18px;
            color:var(--om-section-title);
            line-height:1.2;
            position:relative;
            padding-bottom:18px;
        }
        .om-shorts-section-title::after{
            content:'';
            position:absolute;
            left:50%; bottom:0;
            transform:translateX(-50%);
            width:50px; height:3px;
            background:var(--om-accent);
            border-radius:2px;
        }
        .om-shorts-intro{
            text-align:center;
            font-size:16px;
            color:var(--om-text);
            max-width:760px;
            margin:0 auto 40px;
            line-height:1.6;
        }

        /* ---------- CAROUSEL CONTAINER ---------- */
        .om-shorts-carousel-outer{
            position:relative;
        }
        .om-shorts-swiper{
            padding:10px 5px 50px;
            overflow:hidden;
        }

        /* ---------- EGYENLŐ MAGASSÁGÚ KÁRTYÁK ---------- */
        .om-shorts-swiper .swiper-wrapper{
            align-items:stretch;
        }
        .om-shorts-swiper .swiper-slide{
            height:auto;
            display:flex;
            flex-direction:column;
        }
        .om-shorts-swiper .swiper-slide > .om-short{
            flex:1;
            width:100%;
        }

        /* ---------- KÁRTYA ---------- */
        .om-short{
            background:var(--om-card-bg);
            border-radius:14px;
            overflow:hidden;
            box-shadow:0 6px 22px rgba(0,0,0,.08);
            display:flex;
            flex-direction:column;
            transition:transform .25s ease, box-shadow .25s ease;
        }
        .om-short:hover{
            transform:translateY(-4px);
            box-shadow:0 14px 30px rgba(0,0,0,.12);
        }

        /* ---------- THUMBNAIL ---------- */
        .om-thumb{
            position:relative;
            aspect-ratio:9/16;
            background:#000;
            cursor:pointer;
            overflow:hidden;
        }
        .om-thumb img{
            width:100%; height:100%;
            object-fit:cover;
            display:block;
            transition:transform .35s ease;
        }
        .om-thumb:hover img{transform:scale(1.04)}
        .om-play{
            position:absolute;
            top:50%; left:50%;
            transform:translate(-50%,-50%);
            width:68px; height:48px;
            opacity:.92;
            transition:opacity .2s, transform .2s;
            pointer-events:none;
        }
        .om-thumb:hover .om-play{
            opacity:1;
            transform:translate(-50%,-50%) scale(1.08);
        }
        .om-play svg{
            width:100%; height:100%;
            filter:drop-shadow(0 2px 6px rgba(0,0,0,.4));
        }

        /* ---------- KÁRTYA SZÖVEG ---------- */
        .om-card-body{
            padding:18px 20px 14px;
            flex:1;
        }
        .om-short-title{
            font-size:16px;
            font-weight:700;
            margin:0 0 10px;
            color:var(--om-title);
            line-height:1.35;
        }
        .om-short.om-uppercase .om-short-title{
            text-transform:uppercase;
            letter-spacing:.5px;
        }
        .om-short-desc{
            font-size:14px;
            color:var(--om-text);
            margin:0;
            line-height:1.55;
        }

        /* ---------- CTA BADGE (mint az árcímke) ---------- */
        .om-card-footer{
            padding:0 20px 22px;
        }
        .om-cta-badge{
            display:inline-flex;
            align-items:center;
            gap:7px;
            padding:9px 16px;
            background:var(--om-accent);
            color:#fff;
            font-weight:700;
            font-size:14px;
            line-height:1;
            border:none;
            border-radius:6px;
            cursor:pointer;
            transition:opacity .2s, transform .15s;
        }
        .om-cta-badge:hover{
            opacity:.9;
            transform:translateY(-1px);
        }
        .om-cta-badge svg{flex-shrink:0}

        /* ---------- NAVIGÁCIÓ (dgbc plugin stílus, ETmodules ikon) ---------- */
        .om-shorts-nav{
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            width:53px; height:53px;
            background-color:#fff;
            border:none;
            border-radius:0;
            box-shadow:none;
            color:var(--om-accent);
            cursor:pointer;
            z-index:10;
            padding:0;
            display:flex;
            align-items:center;
            justify-content:center;
            transition:all .2s ease;
        }
        .om-shorts-nav::after{
            content:attr(data-icon);
            font-family:"ETmodules" !important;
            font-size:53px;
            font-weight:400;
            font-style:normal;
            font-variant:normal;
            line-height:.96em;
            display:inline-block;
            vertical-align:super;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
            speak:none;
            text-transform:none;
            transition:all .2s ease;
        }
        .om-shorts-nav:hover{
            color:#fff;
            background-color:var(--om-accent);
        }
        .om-shorts-prev{left:-70px}
        .om-shorts-next{right:-70px}
        .om-shorts-nav.swiper-button-disabled{
            opacity:.35;
            cursor:default;
            background-color:#fff !important;
            color:var(--om-accent) !important;
        }

        /* ---------- LAP-JELZŐK ---------- */
        .om-pag{
            text-align:center;
            position:absolute !important;
            bottom:10px !important;
            left:0; right:0;
        }
        .om-pag .swiper-pagination-bullet{
            width:10px; height:10px;
            background:#cfd4d6;
            opacity:1;
            margin:0 5px !important;
            transition:background .2s;
        }
        .om-pag .swiper-pagination-bullet-active{
            background:var(--om-accent);
        }

        /* ---------- RESZPONZIVITÁS ---------- */
        @media (max-width:1100px){
            .om-shorts-prev{left:5px}
            .om-shorts-next{right:5px}
            .om-shorts-nav{width:44px; height:44px}
            .om-shorts-nav::after{font-size:44px}
        }
        @media (max-width:600px){
            .om-shorts-section-title{font-size:26px}
            .om-shorts-intro{font-size:15px; margin-bottom:30px}
            .om-shorts-nav{display:none}
        }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
        <script>
        (function(){
            function omShortsInit(){
                if (typeof Swiper === 'undefined'){ setTimeout(omShortsInit, 100); return; }

                document.querySelectorAll('.om-shorts-swiper').forEach(function(el){
                    if (el.dataset.omInit) return;
                    el.dataset.omInit = '1';

                    var ds    = parseFloat(el.dataset.desktop) || 4;
                    var ts    = parseFloat(el.dataset.tablet)  || 3;
                    var ms    = parseFloat(el.dataset.mobile)  || 1.2;
                    var ap    = el.dataset.autoplay === '1';
                    var apd   = parseInt(el.dataset.autoplayDelay, 10) || 3000;
                    var lp    = el.dataset.loop === '1';
                    var poh   = el.dataset.pauseHover === '1';
                    var slideCount = el.querySelectorAll('.swiper-slide').length;
                    var outer = el.closest('.om-shorts-carousel-outer');

                    // Ha nincs elég slide a loop-hoz, kapcsoljuk ki (Swiper követelmény)
                    if (lp && slideCount <= ds) { lp = false; }

                    var config = {
                        slidesPerView: ms,
                        spaceBetween: 18,
                        watchOverflow: true,
                        loop: lp,
                        pagination: { el: outer.querySelector('.om-pag'), clickable: true },
                        navigation: {
                            nextEl: outer.querySelector('.om-shorts-next'),
                            prevEl: outer.querySelector('.om-shorts-prev')
                        },
                        breakpoints: {
                            640:  { slidesPerView: 2,  spaceBetween: 18 },
                            900:  { slidesPerView: ts, spaceBetween: 22 },
                            1200: { slidesPerView: ds, spaceBetween: 26 }
                        }
                    };

                    if (ap) {
                        config.autoplay = {
                            delay: apd,
                            disableOnInteraction: false,
                            pauseOnMouseEnter: poh
                        };
                    }

                    new Swiper(el, config);
                });

                function playVideo(card){
                    var thumb = card.querySelector('.om-thumb');
                    if (!thumb) return;
                    var id = card.dataset.video;
                    if (!id) return;
                    var iframe = document.createElement('iframe');
                    iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&modestbranding=1&playsinline=1';
                    iframe.allow = 'accelerometer;autoplay;encrypted-media;gyroscope;picture-in-picture';
                    iframe.allowFullscreen = true;
                    iframe.style.cssText = 'width:100%;height:100%;border:0;position:absolute;inset:0';
                    thumb.innerHTML = '';
                    thumb.appendChild(iframe);
                    // Videó indult -> autoplay állítás
                    var swiperEl = card.closest('.om-shorts-swiper');
                    if (swiperEl && swiperEl.swiper && swiperEl.swiper.autoplay) {
                        swiperEl.swiper.autoplay.stop();
                    }
                }

                document.querySelectorAll('.om-thumb').forEach(function(t){
                    if (t.dataset.omClick) return;
                    t.dataset.omClick = '1';
                    var card = t.closest('.om-short');
                    t.addEventListener('click', function(){ playVideo(card); });
                    t.addEventListener('keydown', function(e){
                        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); playVideo(card); }
                    });
                });

                document.querySelectorAll('.om-cta-badge').forEach(function(b){
                    if (b.dataset.omClick) return;
                    b.dataset.omClick = '1';
                    var card = b.closest('.om-short');
                    b.addEventListener('click', function(e){
                        e.preventDefault();
                        if (card) playVideo(card);
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', omShortsInit);
            } else {
                omShortsInit();
            }
        })();
        </script>
        <?php
    }
}

new OM_Shorts_Plugin();
