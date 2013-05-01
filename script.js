
jQuery(function(){

    var snippetMap = {};
    var loadingImg = '<img src="'+DOKU_BASE+'lib/images/throbber.gif" class="load" alt="loading..." />';
    showSnippet(jQuery(".editions_editionlist a"));

    function showSnippet($editionLink) {
        $editionLink.removeAttr('title').hover(
            function () {
                var $this        = jQuery(this);
                var target       = $this.attr("href");
                var hashPos      = target.lastIndexOf('#');
                var targetURL    = target.substring(0, hashPos);
                var targetAnchor = target.substring(hashPos+1, target.length);
                var edition      = $this.attr('class');

                fillSnippet(targetURL, targetAnchor, edition);
            },
            function () {
                var $this        = jQuery(this);
                var target       = $this.attr("href");
                var hashPos      = target.lastIndexOf('#');
                var targetURL    = target.substring(0, hashPos);
                var targetAnchor = target.substring(hashPos+1, target.length);

                jQuery('#load__'+targetAnchor).empty().hide();
            }
        );
    }

    function fillSnippet(targetURL, targetAnchor, edition) {
        var $snippet = jQuery('#load__'+targetAnchor).show().html(loadingImg);
        var snippetMapKey = edition+'-'+targetAnchor;
        var snippetMapValue = snippetMap[snippetMapKey];

        if (snippetMapValue) {
            $snippet.html(snippetMapValue);
        } else {
            $snippet.load(targetURL+' #'+targetAnchor, function(response, status, xhr) {
                if (status == "success") {
                    snippetMap[snippetMapKey] = jQuery(this).clone().html();
                }
            });
        }
    }

});
