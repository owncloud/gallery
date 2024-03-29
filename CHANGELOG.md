# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [Unreleased] - xxxx-xx-xx

## [16.1.2] - 2021-10-05

### Fixed

- Show proper name in file menu - Fileactions: get rid of deprecate register function, adjust name, displayName and icon [#836](https://github.com/owncloud/gallery/pull/836)
- Don't return files when accessing a share files drop [#839](https://github.com/owncloud/gallery/pull/839)
- Unbreak gallery with gallery.cnf [#840](https://github.com/owncloud/gallery/pull/840)
- Align password public protected link, with new Login-UI - Apply new auth form design [#846](https://github.com/owncloud/gallery/pull/846)


## [16.1.1] - 2018-12-11

### Changed

- Set max version to 10 because core platform is switching to Semver

## [16.1.0] - 2018-07-11

### Fixed

- Rotate images in the browser [#764](https://github.com/owncloud/gallery/pull/764)
- Do not break if mount is not available [#739](https://github.com/owncloud/gallery/pull/739)
- Fix to work properly with 10.0.9 [738](https://github.com/owncloud/gallery/pull/738)

[Unreleased]: https://github.com/owncloud/gallery/compare/v16.1.2...master
[16.1.2]: https://github.com/owncloud/gallery/compare/v16.1.1...v16.1.2
[16.1.1]: https://github.com/owncloud/gallery/compare/v16.1.0...v16.1.1
[16.1.0]: https://github.com/owncloud/gallery/compare/v16.0.2...v16.1.0


# Archived entries

gallery (15.0.0)
* Drag and drop files and folders owncloud/gallery#405 (@oparoz)
* Upload straight from the app owncloud/gallery#25 (@oparoz)
* Show thumbnails as soon as they're received owncloud/gallery#29 (@oparoz)
* Add missing options in share dialogue owncloud/gallery#565 owncloud/gallery#213 (@imjalpreet)
* Add flickr style gradients (@raghunayyar)
* Full Album Name is now shown on hovering (@imjalpreet)
* Fix the download button on public pages owncloud/gallery#554 (@imjalpreet)
* Close the share dialogue when performing another action owncloud/gallery#545 (@viraj96)
* PHP7 compatibility

gallery (14.5.0)
* Make the slideshow controls work with a light background owncloud/gallery#51 (@jancborchardt @oparoz)
* Replace feof()/fread() errors when reading some files with error messages owncloud/gallery#395 (@oparoz)

gallery (14.4.0)
* More gallery.cnf parameter validation owncloud/gallery#477 (@oparoz)
* Bring back smooth scrolling on mobile owncloud/gallery#524 (@oparoz)
* Improve SVG cleanup after purification owncloud/gallery#481 (@oparoz)
* Big area for "next" and "prev" buttons owncloud/gallery#457 (@0xb0ba)
* Introduce support for shorter URLs owncloud/gallery#517 (@oparoz)
* Remove IE8 support owncloud/gallery#514 (@oparoz)

gallery (14.3.0)
* Add visual feedback when clicking on albums owncloud/gallery#416 (@oparoz)
* Protect app from bad JPEGs owncloud/gallery#420 (@oparoz)
* Fix empty album presentation when images can't be processed (@oparoz)
* More efficient breadcrumbs (@oparoz)
* A few minor visual glitches (@oparoz)

gallery (14.2.0)
* [security] Purify SVGs owncloud/gallery#373 (@oparoz)
* Restore sharing on 8.2 owncloud/gallery#359 (@PVince81 @icewind1991 @oparoz)
* Fix empty page for public galleries owncloud/gallery#414 owncloud/gallery#418 (@henni @oparoz)
* Fix displaying album icon on Chrome owncloud/gallery#397 (@oparoz)

gallery (14.1.0)
* [security] Update DomPurify to fix a vulnerability in Firefox and to fix browser problems (@oparoz)
* Fix problems with sub-album thumbnails on HighDPI screens owncloud/gallery#377 (@oparoz)
* Fix ordering of thumbnails in sub-albums owncloud/gallery#382 (@oparoz)
* Update the "no pictures found" landing page to be in line with Files owncloud/gallery#290 (@jancborchardt)

gallery (14.0.0)
* Make Gallery the new official app to display images in ownCloud
* [security] Sanitize markdown descriptions owncloud/gallery#295 (@oparoz/@LukasReschke)
* [security] Send SVGs as text files on download endpoints owncloud/gallery#347 (@oparoz/@LukasReschke)
* [security] Keep error messages in cookies owncloud/gallery#296 (@oparoz/@LukasReschke)
* [security] Print filenames as text, not html owncloud/gallery#294 (@oparoz/@LukasReschke)
* New RESTful API to serve config, file list, thumbnails or previews to apps owncloud/gallery#5 (@oparoz)
* Make button to toggle the background colour optional owncloud/gallery#226 (@oparoz)
* Pick a background colour for the photowall owncloud/gallery#288 (@oparoz)
* Add a busy spinner whilst loading thumbs owncloud/gallery#130 (@oparoz)
* Turn the gallery/files view button into a switch owncloud/gallery#145 (@oparoz/@jancborchardt)
* Use SVG icons owncloud/gallery#331 (@oparoz)
* Send the media type icon if the browser can't parse a preview owncloud/gallery#346 (@oparoz)
* Restore IE9/10 compatibility (@oparoz)
* Restore IE8 compatibility for the slideshow (@oparoz)
* Fix external share permission logic owncloud/gallery#218 (@oparoz)
* Fix native SVG previews on IE owncloud/gallery#238 (@oparoz)
* Fix slideshow controls contrast, remove duplicate code owncloud/gallery#222 (@jancborchardt )
* Fix slideshow when the folder contains a single image owncloud/gallery#246 (@oparoz)

gallery (13.0.0)
* Caching and JS performance improvements (@oparoz)
* Make the app compatible with the "back" button #160 (@oparoz,@setnes)
* Fix broken view when going back to a nested folder #206 (@oparoz)
* Add missing folder navigation if user switched to an empty folder from the Files app #76 (@oparoz)
* Save the sorting order for the current session #198 (@oparoz)
* Exit the slideshow with one click when in fullscreen mode #200 (@oparoz)
* Render native SVGs directly and use fallback for older browsers (@oparoz)
* fix image titles / labels #191 (@jancborchardt )

gallery (12)
* Use IDs instead of paths to retrieve thumbnails #27 (@oparoz)
* Cache albums instead of parsing the folders every time #41 (@oparoz)
* Dont send back media type icon #174 (@oparoz)
* Fix rotation on mobile devices #142 (@setnes, @oparoz)
* Fix JS loader regexp so that it only works on shared links #138 (@oparoz)
* Refresh slideshow picture when it's been updated #149 (@oparoz)
* Limit the number of previews we generate #157 (@setnes)
* "Add to ownCloud" straight from the app #144 (@oparoz)
* Improve name sorting #189 (@oparoz)
* Create direct download link by adding filename to public link #156 (@oparoz)
* Add the possibility to enable federated shares on 8.0 #201 (@oparoz)
* Fix infobox resizing #165 (@oparoz)
* Fix public reshares #163 (@oparoz)
* Catch exceptions thrown by isMounted() #162 (@oparoz)
* Make native SVG support optional #187 (@oparoz)
* Replace logo-wide on share page with better icon + text #153 (@jancborchardt)
* Fix EOF for some patches (@setnes, @oparoz)
* Fix bullet point style for Markdown text #147 (@oparoz)

gallery (8.1.11)
* Keep the browsing position in the Files app when exiting the slideshow #126 (@oparoz)

gallery (2.0.10)
* Add the possibility to enable external shares via the configuration file (only works on 8.1) (@oparoz)
* Fix slideshow loading for when the app is installed in a custom folder #121 (@oparoz)
* Don't load the slideshow on the files public page for single items #123 (@oparoz)
* Make album labels tighter #117 (@oparoz)
* Only load supported image formats in the Files slideshow #127 (@oparoz)

gallery (2.0.9)
* Restore the ability to see images located on local, shared folders #120 (@oparoz)
* Fix Url builder for public side (@oparoz)

gallery (2.0.8)
* Introduce album configuration via a text file #85 (@oparoz)
* Introduce buttons to sort content by name and upload date #90 and #91 (@oparoz)
* Introduce image labels #106 (@oparoz)
* Shrink the breadcrumb when there is very little space left #108 (@oparoz)
* Remove permission loading routine and send the information about the current folder with the files (@oparoz)
* Remove extra slash in preview URL #100 (@oparoz)
* Fix thumbnail rendering so that the view doesn't break when a new request is made while the previous one is still not completed #56 (@oparoz)
* Fix searching algorithm to only return a maximum of 4 images per album #112 (@oparoz)
* Javascript cleanup #57 (@oparoz)

gallery (2.0.7)
* Improved IE11 compatibility

gallery (2.0.6)
* Fix the logic implemented to limit the number of thumbnails to preload #60 (@oparoz)
* Fix all thumbnails instead of just the square ones. Preview in core can return previews of the wrong dimensions, so we fix them (@oparoz)
* Always fill the albums with up to 4 pictures if we find enough at a lower level #65 (@oparoz)
* Fix converted SVGs #63 (@oparoz)
* Ignore broken files and folders when building a map of the current folder #69 (@oparoz)
* Only return pictures of the first 2nd level sub-folder which contains pictures bug #79 (@oparoz)
* Always show the Gallery button in the files app, making it possible to start the Gallery from any folder #73 (@oparoz)

gallery (2.0.5)
* Fix performance issues related to the initialisation of the view by only loading folders and media files belonging to the current folder #17 (@oparoz)
* Don't scan folders containing a '.nomedia' file #58 (@oparoz based on @jsalatiel's idea)
* Don't scan folders stored on external storage (@oparoz)
* Disable search field (since it's not working) #37 (@oparoz)
* Display an error message in the slideshow if something went wrong when generating the full screen preview (@oparoz)
* Change the default background of large previews to white so that dark images with a transparent background can be viewed #40 (@oparoz)
* Let the user change the background colour of large previews #40 (@oparoz)
* Fix the path to media type icons so that it works on all configurations (@oparoz)
* Calculate picture size properly on High DPI devices (@oparoz)
* Fix controls on mobile devices (@oparoz)

gallery (2.0.0)
* ownCloud 8 only
* Includes patch to previsualise Raw files (@oparoz)

gallery (1.0.3)
* First release using the AppFramework (@oparoz)
* ownCloud 7 compatible (@oparoz)
* Supports all the media types ownCloud has been configured to convert to PNG (@oparoz)
* Download a file straight from the slideshow (@libasys,@oparoz)
* Download all the pictures shown in a public gallery (@oparoz)
* Fullscreen previews with zoom support (@davidrapin, @oparoz)
* Batch loading of thumbnails (@icewind1991, @oparoz)
* Prettier albums (@libasys,@oparoz)
* Native SVG support, without conversion (@oparoz)
* Loads the gallery exactly where you were in the Files app (@oparoz)
* Various fixes to the preview generator (@oparoz)
* Make the gallery button available in the Files app (non-shared area) (@oparoz)
* Generate gallery links straight from the share dialogue in the Files app (@oparoz)
* Includes performance patches to avoid constantly generating new previews (@oparoz)
