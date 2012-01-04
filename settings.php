<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2>Hierarchy Settings</h2>
    <form action="options.php" method="post">
        <div id="poststuff" class="metabox-holder">
            <?php settings_fields( HIERARCHY_PREFIX . 'settings' ); ?>
            <?php do_settings_sections( HIERARCHY_PREFIX . 'settings' ); ?>
        </div>
        <p class="submit">
            <input type="submit" class="button-primary" value="Save Settings" />
        </p>
    </form>
</div>
