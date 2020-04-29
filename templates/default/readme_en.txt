                <p><strong>This document has been translated automatically. Please notice that there might be some errors or non-exact matches to the actual wording used in the backend.</strong></p>

                <h1>Journal</h1>

                <p>News with images (short: NWI) makes it easy to create news pages or articles with several functions:</p>
                <ul>
                    <li>Post picture</li>
                    <li>integrated picture gallery (Masonry or Fotorama)</li>
                    <li>optional 2nd content area</li>
                    <li>Sort articles with drag &amp; drop</li>
                    <li>Moving / copying articles between groups and sections</li>
                    <li>Import of entries from the WBCE/WebsiteBaker modules Topics and &quot;Classic&quot; News</li>
                </ul>

                <h2>Download</h2>
                <p>The module is a core module as of WBCE CMS 1.4 and installed by default. In addition, the download is available in the [WBCE CMS Add-On Repository] (<a href="https://addons.wbce.org">https://addons.wbce.org</a>).</p>

                <h2>License</h2>
                <p>NWI is under [GNU General Public License (GPL) v3.0] (<a href="http://www.gnu.org/licenses/gpl-3.0.html">http://www.gnu.org/licenses/gpl-3.0.html</a>).</p>

                <h2>System requirements</h2>
                <p>There are no special requirements; if WBCE CMS is running fine, NWI should work too.</p>

                <h2>installation</h2>
                <ol>
                    <li>If necessary, download the latest version from [AOR] (<a href="https://addons.wbce.org">https://addons.wbce.org</a>)</li>
                    <li>Install/Update like any other WBCE module via add-ons &amp; gt; modules</li>
                </ol>

                <h2>Use</h2>
                <h3>Getting started and writing</h3>
                <ol>
                    <li>Create a new page with &quot;Journal&quot;</li>
                    <li>Click on &quot;Add post&quot; to create a new post or on the headline of an existing post to edit it</li>
                    <li>Fill out the heading and, if necessary, further fields, if necessary select images. The function of the input fields is probably self-explanatory.</li>
                    <li>Click &quot;Save&quot; or &quot;Save and go back&quot;</li>
                    <li>Repeat steps 2. - 4. a few times and look at the whole in the frontend</li>
                </ol>
                <p>Basically, NWI can be combined with other modules on a page or in a block, but then it can, as with any module that generates its own detail pages, come to results that do not meet the expected / desirable results.</p>

                <h3>pictures in the post</h3>
                <p>For each post a preview image can be uploaded, which is shown on the overview page and if necessary the post page. In addition, it is possible to add any number of images to a post, which are displayed as a picture gallery. The gallery presentation is shown either as a Fotorama gallery (thumbnails, full-width image) or as a Masonry gallery (picture mosaic).</p>
                <p>Which gallery script is used is set for all articles in the settings of each section.</p>
                <p>The gallery images are uploaded as the post is saved, and can then be captioned, resorted, or deleted.</p>
                <p>When uploading files with the same name as already existing images, the existing files are not overwritten, but the following files are supplemented with consecutive numbering (bild.jpg, bild_1.jpg, etc.).</p>
                <p>The management of the pictures takes place only over the post page, not over the WBCE media administration, since NWI does not &quot;know&quot; otherwise, where which images belong / are missing etc.</p>

                <h3>Groups</h3>
                <p>Articles can be assigned to groups. On the one hand, this has an influence on the order (the articles are sorted according to the group and then according to a further criterion to be specified), and on the other hand, it is possible to generate topic-specific overview pages. These can then be accessed via the URL of the NWI page with the parameter g?=GROUP_ID, e.g. news.php?g=2.</p>
                <p>A post can be assigned to one group only.</p>
                <p>Single or multiple articles can be copied and moved between groups.</p>

                <h3>Tags</h3>
                <p>This function is only available if "expert mode" has been activated in the settings and tags have been created.</p>
                <p>Articles can be assigned to one or more tags. Depending on the configuration, these tags are then displayed in the front end on the article overview and / or the detailed view and are linked to the overview of all articles for this tag.</p>
                <p>Tags are made available centrally for all articles in the section from the article overview ("tags" tab) and can then be selected in the article detail view.</p>
                <p>Global tags are available in all NWI sections, e.g. also on other pages of the website.</p>
                <p>Once created, tags can be modified and it's also possible to define own colors for each tag.</p>

                <h3>2nd Block</h3>
                <p>This function is only available if "expert mode" has been activated in the settings and "Use second block" is chosen.</p>

                <p>If supported by the template, content can be displayed in a second block (e.g. an aside column). This can be either recurring content stored in the settings, post-specific content (post image, short  text, etc.) or texts stored directly in the post that were entered in the input field for the 2nd block.</p>

                <h3>import function</h3>
                <p>As long as no post has been made in the respective NWI section, articles from the classic news module, other NWI sections as well as topics can be imported automatically.
                The page settings of the source page are applied. When importing Topics articles, however, manual rework is still required, if the &quot;Additional Images&quot; function was used in Topics.</p>

                <h3>Copy / move articles</h3>
                <p>From the post overview in the backend, individual, multiple selected or all (marked) articles within a section can be copied or either copied or moved between different sections (even on different pages). Copied articles are always initially not visible in the frontend (Active selection: &quot;no&quot;).</p>

                <h3>Delete articles</h3>
                <p>You can delete single, multiple selected or all (selected) articles from the post overview. After confirming, the respective articles are irrevocable <strong> DESTROYED </strong>, there is <strong> no </strong> way to restore them!</p>

                <h2>configuration</h2>

                <h3>Expert mode</h3>
                <p>If "Expert mode" is activated, additional input fields are available in the settings (2nd block), in the post overview (tags) and in the detailed post view (tag assignment, 2nd block).</p>

                <p><strong>Attention:</strong> If you switch between activated and deactivated expert mode, you will be returned to the entry overview, other changes to the settings will <strong>not</strong> be saved.</p>

                <h3>overview page</h3>
                <ul>
                    <li><strong> Order by </strong>: definition of the order of articles (custom = manual definition, articles appear as they are arranged in the backend, start date / expiry date / submitted (= creation date) / Submission ID: each descending order according to the corresponding criterion)</li>
                    <li><strong> Articles per page </strong>: Selection of how many entries (teaser image / text) per page should be displayed</li>
                    <li><strong> header, post loop, footer </strong>: HTML code to format the output</li>
                    <li><strong> Resize preview image to </strong> Width / height of image in pixels. <strong> no </strong> automatic recalculation will take place if changes are made, so it makes sense to think in advance about the desired size and then not change the value again.<br />
                    The image is only available in the specified resolution. If it is to be used in different sizes (small on the overview page, larger on the post page), set the image size to the value for the larger display and reduce the image on the overview page using CSS.</li>
                </ul>

                <p>Supported placeholders:</p>
                <h4>Header / Footer</h4>
                <ul>
                    <li>[NEXT_PAGE_LINK] &quot;Next page&quot;, linked to the next page (if the overview page is split over several pages),</li>
                    <li>[NEXT_LINK], &quot;Next&quot;, s.o.,</li>
                    <li>[PREVIOUS_PAGE_LINK], &quot;Previous Page&quot;, s.o.,</li>
                    <li>[PREVIOUS_LINK], &quot;Previous&quot;, s.o.,</li>
                    <li>[OUT_OF], [OF], &quot;x of y&quot;,</li>
                    <li>[DISPLAY_PREVIOUS_NEXT_LINKS] &quot;hidden&quot; / &quot;visible&quot;, depending on whether pagination is required</li>
                    <li>[BACK] URL of the news overview page</li>
                    <li>[TEXT_BACK] &quot;back&quot;</li>
                </ul>

                <h4>post loop</h4>
                <ul>
                    <li>[PAGE_TITLE] headline of the page,</li>
                    <li>[GROUP_ID] ID of the group to which the post is assigned, for articles without group &quot;0&quot;</li>
                    <li>[GROUP_TITLE] Title of the group to which the post is assigned, for articles without group &quot;&quot;,</li>
                    <li>[GROUP_IMAGE] Image (&lt;img src ... /&gt;) of the group to which the post is assigned for articles without group &quot;&quot;,</li>
                    <li>[DISPLAY_GROUP] <em> inherit </em> or <em> none </em>,</li>
                    <li>[DISPLAY_IMAGE] <em> inherit </em> or <em> none </em>,</li>
                    <li>[TITLE] title (heading) of the article,</li>
                    <li>[IMAGE] post image (&lt;img src = ... /&gt;),</li>
                    <li>[SHORT] short text,</li>
                    <li>[LINK] Link to the article detail view,</li>
                    <li>[MODI_DATE] date of the last change of the post,</li>
                    <li>[MODI_TIME] Time (time) of the last change of the post,</li>
                    <li>[CREATED_DATE] Date when the post was created,</li>
                    <li>[CREATED_TIME] time at which the post was created,</li>
                    <li>[PUBLISHED_DATE] start date,</li>
                    <li>[PUBLISHED_TIME] start time,</li>
                    <li>[USER_ID] ID of the creator of the post,</li>
                    <li>[USERNAME] username of the creator of the post,</li>
                    <li>[DISPLAY_NAME] Display name of the creator of the post,</li>
                    <li>[EMAIL] Email address of the creator of the post,</li>
                    <li>[TEXT_READ_MORE] &quot;Show details&quot;,</li>
                    <li>[SHOW_READ_MORE], <em> hidden </em> or <em> visible </em>,</li>
                    <li>[GROUP_IMAGE_URL] URL of the group image,</li>
                    <li>[CONTENT_LONG] long text,</li>
                    <li>[TAGS] The tags assigned to the post</li>
                </ul>

                <h3>post view</h3>
                <ul>
                    <li><strong> Message Header, Content, Footer, Block 2 </strong>: HTML code for formatting the message</li>
                </ul>
                <p>Supported placeholders:</p>
                <h4>Message Header, Message Footer, Block 2</h4>
                <ul>
                    <li>[PAGE_TITLE] headline of the page,</li>
                    <li>[GROUP_ID] ID of the group to which the post is assigned, for articles without group &quot;0&quot;</li>
                    <li>[GROUP_TITLE] Title of the group to which the post is assigned, for articles without group &quot;&quot;,</li>
                    <li>[GROUP_IMAGE] Image (&lt;img src ... /&gt;) of the group to which the post is assigned for articles without group &quot;&quot;,</li>
                    <li>[DISPLAY_GROUP] <em> inherit </em> or <em> none </em>,</li>
                    <li>[DISPLAY_IMAGE] <em> inherit </em> or <em> none </em>,</li>
                    <li>[TITLE] title (heading) of the article,</li>
                    <li>[IMAGE] post image (&lt;img src = ... /&gt;),</li>
                    <li>[IMAGE_URL] post image (http://www.example.com/media/.journal/file.jpg),</li>
                    <li>[CONTENT_SHORT] short text,</li>
                    <li>[MODI_DATE] date of the last change of the post,</li>
                    <li>[MODI_TIME] Time (time) of the last change of the post,</li>
                    <li>[CREATED_DATE] Date when the post was created,</li>
                    <li>[CREATED_TIME] time at which the post was created,</li>
                    <li>[PUBLISHED_DATE] start date,</li>
                    <li>[PUBLISHED_TIME] start time,</li>
                    <li>[USER_ID] ID of the creator of the post,</li>
                    <li>[USERNAME] username of the creator of the post,</li>
                    <li>[DISPLAY_NAME] Display name of the creator of the post,</li>
                    <li>[EMAIL] Email address of the creator of the post,</li>
                    <li>[TAGS] The tags assigned to the post</li>
                </ul>

                <h4>news content</h4>
                <ul>
                    <li>[CONTENT] complete post Content (short+long) (HTML),</li>
                    <li>[IMAGES] Images / Gallery HTML,</li>
                    <li>[CONTENT_SHORT] short text,</li>
                    <li>[CONTENT_LONG] long text,</li>
                    <li>[TAGS] The tags assigned to the post</li>
                </ul>
                <h3>Gallery / Picture Settings</h3>
                <ul>
                    <li><strong> Image Gallery </strong>: Selection of the gallery script to use. Please note that any customizations made to the gallery code in the Message Content field will be lost in case of a change.</li>
                    <li><strong> Image loop </strong>: HTML code for the representation of a single image must match the respective gallery script</li>
                    <li><strong>Max. Image size in bytes </strong>: File size per image file, why this must now be specified in bytes and not in readable KB or MB, I just do not know</li>
                    <li><strong> Resize gallery images to / Thumbnail size width x height </strong>: exactly same. <strong> no </strong> automatic recalculation will take place if changes are made, so it makes sense to think in advance about the desired size and then not change the value again.</li>
                    <li><strong> Crop </strong>: See the explanation on the page.</li>
                </ul>
                <h3>2nd block</h3>
                <p>Optionally, a second block can be displayed if the template supports it.</p>