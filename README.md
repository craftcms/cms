<p align="center"><a href="https://craftcms.com/" target="_blank"><img width="312" height="90" src="https://craftcms.com/craftcms.svg" alt="Craft CMS"></a></p>

About Craft CMS
---------------

Craft is a content-first CMS that aims to make life enjoyable for developers and content managers alike. It is optimized for bespoke web and application development, offering developers a clean slate to build out exactly what they want, rather than wrestling with a theme. 

Learning Craft
--------------

Several great learning resources are out there:

- Craft’s [official documentation](https://github.com/craftcms/docs) is the best place to start.
- The official [demo site](https://demo.craftcms.com/) offers a quick and effortless way to start exploring a pre-built Craft site.
- [Mijingo](https://mijingo.com/craft) offers a collection of video courses, covering the full spectrum from beginner to advanced.
- [Envato Tuts+](https://webdesign.tutsplus.com/categories/craft-cms/courses) offers a collection of video courses as well.
- You can find helpful guides and recipes on [Straight Up Craft](http://straightupcraft.com/) and [Craft Cookbook](http://craftcookbook.net/).
- Get help from others in the Craft community on [Craft Slack](https://craftcms.com/community#slack) and [Craft CMS Stack Exchange](http://craftcms.stackexchange.com/). 

Installing Craft 3 Beta
-----------------------

To install the Craft 3 Beta, follow these instructions.

1. Install Composer if you don’t have it already.
  - [macOS/Linux/Unix instructions](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
  - [Windows instructions](https://getcomposer.org/doc/00-intro.md#installation-windows)
2. Run this command in your terminal (substituting `path` with the path the project should be created at) to create a new Craft project based on this package:

        php composer.phar create-project craftcms/craft path

3. Copy the `.env.example` file at the root of the project to a new `.env` file, and fill in your database connection settings within it.
4. Create a new web server, setting its document root to the `web/` folder within the project.
5. Point your web browser to `http://example.com/index.php?p=admin` (substituting `example.com` with your new web server’s host name) to access the Craft installer.
