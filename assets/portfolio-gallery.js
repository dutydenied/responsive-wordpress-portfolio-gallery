/**
 * WordPress Portfolio Gallery plugin -- tag filtering + lightbox.
 * Plain JS, no Jquery dependency. Filtering just toggles the 'hidden' attribute.
 * CSS Grid in portfolio-gallery.css reflows the remaining items automatically
 */
( function() {
    var lightbox = null;
    var lastTrigger = null;

    /**
     * Lightbox is built once then shared by every [portfolio_gallery] instance on page
     * instead of being duplicated via shortcode. This avoids duplicate ids/ARIA targets
     * if the shortcode is used more than once.
     */
    function buildLightbox() {
        var element = document.createElement ( 'div' );
        element.className = 'portfolio-lightbox';
        element.hidden = true;
        element.innerHTML =
            '<div class="portfolio-lightbox__backdrop" data-close></div>' +
            '<div class="portfolio-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Portfolio image preview">' +
                '<button type="button" class="portfolio-lightbox__close" data-close aria-label="Close">&times;</button>' +
                '<img class="portfolio-lightbox__image" src="" alt="">' +
                '<div class="portfolio-lightbox__meta">' +
                    '<h3 class="portfolio-lightbox__title"></h3>' +
                    '<p class="portfolio-lightbox__tags"></p>' +
                    '<a class="portfolio-lightbox__link" href="#">See more info &rarr;</a>' +
    '           </div>' +
            '</div>';
        document.body.appendChild(element);

        element.addEventListener ( 'click',function ( event ) {
            if (event.target.hasAttribute('data-close')) {
                closeLightbox();
            }
        } );

        document.addEventListener( 'keydown',function ( event ) {
            if (event.key === 'Escape' && ! element.hidden ) {
                closeLightbox();
            }
        } );
        return element;
    }

    function getLightbox() {
        if (!lightbox) {
            lightbox = buildLightbox();
        }
        return lightbox;
    }

    function openLightbox(trigger) {
        var box = getLightbox();
        var img = box.querySelector ('.portfolio-lightbox__image');
        var title = box.querySelector ('.portfolio-lightbox__title');
        var tags = box.querySelector ('.portfolio-lightbox__tags');
        var link = box.querySelector( '.portfolio-lightbox__link' );

        img.src = trigger.getAttribute('data-full') || '';
        img.alt = trigger.getAttribute('data-title') || '';
        title.textContent = trigger.getAttribute('data-title') || '';
        tags.textContent = trigger.getAttribute('data-tags-label') || '';
        link.href = trigger.getAttribute('data-permalink') || '#';

        lastTrigger = trigger;
        box.hidden = false;
        document.body.classList.add( 'portfolio-lightbox-open' );
        box.querySelector( '.portfolio-lightbox__close' ).focus();
    }

    function closeLightbox() {
        if (!lightbox || lightbox.hidden) {
            return;
        }
        lightbox.hidden = true;
        document.body.classList.remove( 'portfolio-lightbox-open' );
        if (lastTrigger) {
            lastTrigger.focus();
            lastTrigger = null;
        }
    }

    function initGallery( gallery ) {
        var filterButtons = gallery.querySelectorAll( '.portfolio-gallery__filter' );
        var items = gallery.querySelectorAll ( '.portfolio-gallery__item' );
        var emptyMessage = gallery.querySelector( '.portfolio-gallery__empty' );
        var triggers = gallery.querySelectorAll( '.portfolio-gallery__trigger' );

        function applyFilter( filter ) {
            var visibleCount = 0;

            items.forEach( function ( item ) {
                var tags = ( item.getAttribute( 'data-tags' ) || '').split( /\s+/ );
                var matches = filter === 'all' || tags.indexOf(filter) !== -1;

                if (matches) {
                    item.hidden = false;
                    visibleCount++;
                } else {
                    item.hidden = true;
                }
            });

            if (emptyMessage) {
                emptyMessage.hidden = visibleCount !== 0;
            }
        }

        filterButtons.forEach( function ( button ) {
            button.addEventListener('click', function () {
                filterButtons.forEach(function (btn) {
                    btn.classList.remove('is-active');
                    btn.setAttribute('aria-pressed', 'false');
                });
                button.classList.add('is-active');
                button.setAttribute('aria-pressed', 'true');
                applyFilter(button.getAttribute('data-filter'));
            });
        });

        triggers.forEach( function ( trigger ) {
            trigger.addEventListener ( 'click',function () {
                openLightbox( trigger );
            });
        });
        }

        document.addEventListener ( 'DOMContentLoaded', function() {
            document.querySelectorAll ( '.portfolio-gallery' ).forEach(initGallery);
         });
}) ();