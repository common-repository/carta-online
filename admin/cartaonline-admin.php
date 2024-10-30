<form action='options.php' method='post'>
    <div style="padding-right: 20px;">
        <h1>
            <?php
			echo __('Carta Online Settings', 'carta-online');
            ?>
        </h1>
        <p class="description">
            <?php
			echo __('Visit <a href="https://cartaonline.nl">https://cartaonline.nl</a> for more information.', 'carta-online');
            ?>
        </p>
        <hr />
        <?php
		settings_fields('co_pluginPage');
		do_settings_sections('co_pluginPage');
		submit_button();
        ?>
    </div>
</form>
