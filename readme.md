# AuthOpenIDConnect - LimeSurvey Auth Plugin

## Disclaimer
This plugin is **not maintained** by me anymore.\
Feel free to create a fork if you would like to customize it.

I am really grateful for your pull requests, but as I am not working with PHP and Limesurvey anymore I can't test them and ensure that everything works fine after a merge.

If you would like to continue to maintain this project please open an issue to get in touch with me. ☺

## Install

1. Download the plugin.

2. Install necessary dependencies via composer.
```
composer install
```

3. Zip the plugin with all dependencies installed.
```
zip -r AuthOpenIDConnect AuthOpenIDConnect/*
```

4. Install the plugin in LimeSurvey and fill in the necessary settings in order to connect to your ID Provider.

## Credits
Thanks to Michael Jett for providing the [OpenID Connect Client](https://github.com/jumbojett/OpenID-Connect-PHP)!
