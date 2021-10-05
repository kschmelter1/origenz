(function($) {

  /*****************
   *  Hero Swiper  *
   *****************/

if ($('.block-hero .swiper-container .swiper-slide').length > 1) {
  var mySwiper = new Swiper(".block-hero .swiper-container", {
    loop: true,
    slidesPerView: 1,
    centeredSlides: true,
    pagination: {
      el: '.block-hero .swiper-pagination',
      type: 'bullets',
      clickable: true
    },
    watchOverflow: true
  });
}

if ($('.block-hero .swiper-container .swiper-slide').length) {
  //Place hero animation here
}

  $( document ).ready(function() {
      DNAanim();
  });

})(jQuery);

/********************
*   DNA Animation   *
*********************/

function DNAanim() {
  var $=jQuery.noConflict();

  // Rearranges SVG layers in the DOM (z-index workaround)
  function swapPath(path1, path2) {
    path1.each(function(index) {
      $( this ).insertAfter(path2[index]);
    });
  }
  // Run DNA animation if it exists on page
    if ($('.dna')) {
      // Animates a strand - defaults based on dna size 2
      function spinDNA(args, group, speed){
        var keySpeed = speed || dnaSpeed;
        var order = group || '';
        var pos = args["settings"] || [
          [86, 96], [172, 192], [15, 10]
        ];
        var scale = args["size"] || [.75, 1.25];
        var c1 = $(order+' '+args["class"]+'.top.circle');
        var c2 = $(order+' '+args["class"]+'.bottom.circle');
        var l1 = $(order+' '+args["class"]+'.top.line');
        var l2 = $(order+' '+args["class"]+'.bottom.line');
      var tl = new TimelineMax({
        repeat:-1, yoyo:false
      });
      tl.add('middle-1')
      .to(c1, keySpeed, {
        x: pos[0][0],
        xPercent: pos[0][1],
        scale: scale[0],
        transformOrigin: "center",
        ease: Power1.easeIn,
      }, 'middle-1')
      .to(c2, keySpeed, {
        x: pos[0][0] * -1,
        xPercent: pos[0][1] * -1,
        scale: scale[1],
        transformOrigin: "center",
        ease: Power1.easeIn,
      }, 'middle-1')
      .to(l1, keySpeed, {scaleX: 0, transformOrigin: "right", ease: Power1.easeIn}, 'middle-1')
      .to(l2, keySpeed, {scaleX: 0, transformOrigin: "left", ease: Power1.easeIn}, 'middle-1')
      .call(swapPath, [l1, l2])
      .add('extend-1')
      .to(c1, keySpeed, {
        x: pos[1][0],
        xPercent: pos[1][1],
        scale: 1,
        transformOrigin: "center",
        ease: Power1.easeOut,
        z: "100rem"
      }, 'extend-1')
      .to(c2, keySpeed, {
        x: pos[1][0] * -1,
        xPercent: pos[1][1] * -1,
        scale: 1,
        transformOrigin: "center",
        ease: Power1.easeOut,
        z: "50rem"
      }, 'extend-1')
      .to(l1, keySpeed, {scaleX: 1.2, x: pos[2][0] * -1, transformOrigin: "left", ease: Power1.easeOut}, 'extend-1')
      .to(l2, keySpeed, {scaleX: 1.2, x: pos[2][0], transformOrigin: "right", ease: Power1.easeOut}, 'extend-1')
      .call(swapPath, [c1, c2])
      .add('middle-2')
      .to(c1, keySpeed, {
        x: pos[0][0],
        xPercent: pos[0][1],
        scale: scale[1],
        transformOrigin: "center",
        ease: Power1.easeIn,
      }, 'middle-2')
      .to(c2, keySpeed, {
        x: pos[0][0] * -1,
        xPercent: pos[0][1] * -1,
        scale: scale[0],
        transformOrigin: "center",
        ease: Power1.easeIn,
      }, 'middle-2')
      .to(l1, keySpeed, {scaleX: 0, x: 0, transformOrigin: "left", ease: Power1.easeIn}, 'middle-2')
      .to(l2, keySpeed, {scaleX: 0, x: pos[2][2], transformOrigin: "right", ease: Power1.easeIn}, 'middle-2')
      .call(swapPath, [l2, l1])
      .add('extend-2')
      .to(c1, keySpeed, {
        x: 0,
        xPercent: 0,
        scale: 1,
        transformOrigin: "center",
        ease: Power1.easeOut,
      }, 'extend-2')
      .to(c2, keySpeed, {
        x: 0,
        xPercent: 0,
        scale: 1,
        transformOrigin: "center",
        ease: Power1.easeOut,
      }, 'extend-2')
      .to(l1, keySpeed, {scaleX: 1.2, x: 0, transformOrigin: "right", ease: Power1.easeOut}, 'extend-2')
      .to(l2, keySpeed, {scaleX: 1.2, x: 0, transformOrigin: "left", ease: Power1.easeOut}, 'extend-2')
      .call(swapPath, [c2, c1]);
      return tl;
      }

      var dna1 = {"class": '.dna-1', "settings": [[96, 106], [242, 152], [20, 15, 5]], "size": [.65, 1.35] };
      var dna2 = {"class": '.dna-2', "settings": [[86, 96], [172, 192], [20, 15, 5]], "size": [.75, 1.25] };
      var dna3 = {"class": '.dna-3', "settings": [[63, 68], [120, 160], [15, 8, 5]], "size": [.85, 1.15] };
      var dna4 = {"class": '.dna-4', "settings": [[43, 48], [72, 92], [15, 8, 5]], "size": [.95, 1.05] };

      // Master Timeline
      // We make a master timeline, place individual animations in functions, then add them
      const masterTL = new TimelineMax();
      TweenLite.defaultEase = Linear.easeNone;
      const dnaOffset = "-=4.7";
      var dnaSpeed = 1.25;

      masterTL
      .add( spinDNA(dna2, '.one'))
      .add( spinDNA(dna3, '.two'), dnaOffset)
      .add( spinDNA(dna4, '.three'), dnaOffset)
      .add( spinDNA(dna3, '.four'), dnaOffset)
      .add( spinDNA(dna2, '.five'), dnaOffset)
      .add( spinDNA(dna1, '.six'), dnaOffset)
      .add( spinDNA(dna2, '.seven'), dnaOffset)
      .add( spinDNA(dna3, '.eight'), dnaOffset)
      .add( spinDNA(dna4, '.nine'), dnaOffset)
      .add( spinDNA(dna3, '.ten'), dnaOffset)
      .add( spinDNA(dna2, '.eleven'), dnaOffset)
      ;

  } //End if DNA
}































/////

/**
 * Mockup positioning.
 */
(function( $ ) {
    function sizeWrappers() {
        $('.product-mockup-wrapper').each( function() {
            var wrapper = $(this);
            var wrapperWidth = wrapper.width();

            var wrapperData = wrapper.data();
            var templateDimensions = wrapperData.templateDimensions.split(',');
            var printAreaDimensions = wrapperData.printAreaDimensions.split(',');
            var printAreaOffset = wrapperData.printAreaOffset.split(',');

            var leftPercent = printAreaOffset[0] / templateDimensions[0];
            var topPercent = printAreaOffset[1] / templateDimensions[1];
            var widthPercent = printAreaDimensions[0] / templateDimensions[0];
            var heightPercent = printAreaDimensions[1] / templateDimensions[1];

            var templateAspectRatio = templateDimensions[1] / templateDimensions[0];

            var template = wrapper.find('.product-mockup-template');
            var design = wrapper.find('.product-mockup-design');

            var templateCss = {
                width: wrapperWidth,
                height: wrapperWidth * templateAspectRatio
            };
            var designCss = {
                left: templateCss.width * leftPercent,
                top: templateCss.height * topPercent,
                width: templateCss.width * widthPercent,
                height: templateCss.height * heightPercent
            };
            var wrapperCss = {
                height: templateCss.height
            };

            template.css(templateCss);
            design.css(designCss);
            wrapper.css(wrapperCss);
        } );
    }

    if ( $('.product-mockup-wrapper').length ) {
        $(window).resize( _.throttle( sizeWrappers, 100 ) );
        sizeWrappers();
    }

    // Change mockup colors when the variation is changed.
    var variationsForm = $('form.variations_form');

    if ( variationsForm.length ) {
        var variationId = false;

        function updateMockup() {
            console.log( 'updateMockup', variationId );
            // Update the mockup HTML.
            if ( variationId && origenzVariationTemplateData[variationId] ) {
                // Which color is selected?
                var color = $('.input-border-color:checked').val();
                if ( !color ) {
                    color = 'black';
                }

                console.log( 'templates', origenzVariationTemplateData[variationId] );
                console.log( 'color', color );

                $('.product-mockup-wrapper').replaceWith( origenzVariationTemplateData[variationId][color] );
                sizeWrappers();
            }
        }

        variationsForm.on( 'found_variation', function(event, variation) {
            console.log( 'variation changed', variation);
            variationId = variation.variation_id;
            updateMockup();
        } );

        $( '.input-border-color' ).click( function() {
            updateMockup();
        } );
    }
})(jQuery);




/**
 * Origenz Designer
 */
(function( $ ) {
    if ( !document.getElementById('design-app') )
        return;

    // If there's no state in the app data object, try loading from session storage.
    if ( !origenzAppData.designState || !origenzAppData.designState.step || !origenzAppData.designState.dnaProcessedBy ) {
        var sessionStorageData = sessionStorage.getItem( 'designState' );

        if ( sessionStorageData ) {
            try {
                origenzAppData.designState = JSON.parse( sessionStorageData );
            } catch (e) {
                console.log( 'Invalid JSON in session storage.');
            }
        }
    }

    var defaultRegions = [
        {
            errors: [],
            region: '',
            percent: 0,
            countries: [
                {
                    errors: [],
                    country: '',
                    percent: 0
                }
            ]
        },
        {
            errors: [],
            region: '',
            percent: 0,
            countries: [
                {
                    errors: [],
                    country: '',
                    percent: 0
                }
            ]
        },
        {
            errors: [],
            region: '',
            percent: 0,
            countries: [
                {
                    errors: [],
                    country: '',
                    percent: 0
                }
            ]
        },
        {
            errors: [],
            region: '',
            percent: 0,
            countries: [
                {
                    errors: [],
                    country: '',
                    percent: 0
                }
            ]
        }
    ];

    var initialStep = origenzAppData.designState.step == 3 ? 3 : 1;

    console.log(initialStep);

    var designer = new Vue( {
        el: '#design-app',
        data: {
            step: initialStep,
            errors: [],
            regions: origenzAppData.designState.regions ? origenzAppData.designState.regions : defaultRegions,
            designImages: [],
            appData: origenzAppData,
            dnaProcessedBy: origenzAppData.designState.dnaProcessedBy ? origenzAppData.designState.dnaProcessedBy : '',
            shape: origenzAppData.designState.shape ? origenzAppData.designState.shape : '',
            loading: false
        },
        computed: {
            showRegionsSection: function() {
                return this.dnaProcessedBy && this.dnaProcessedBy != 'Somewhere else';
            },
            countrySectionBulletNumber: function() {
                return this.dnaProcessedBy == 'Somewhere else' ? '2' : '3';
            },
            totalCountries: function() {
                var total = 0;

                for ( var i in this.regions ) {
                    total += this.regions[i].countries.length;
                }

                return total;
            },
            regionListByGroup: function() {
                var groups = [];

                if ( this.dnaProcessedBy != '' ) {
                    var lastGroup = '';
                    var regions = this.appData.regions[this.dnaProcessedBy];
                    for ( var i in regions ) {
                        var region = regions[i];
                        if ( region.group != lastGroup ) {
                            groups.push( {
                                group: region.group,
                                regions: []
                            } );
                        }

                        groups[ groups.length - 1 ].regions.push( region );
                        lastGroup = region.group;
                    }
                }

                console.log(groups);

                return groups;
            },
            chosenDesign: function() {
                var countries = [];

                for ( var r in this.regions ) {
                    for ( var c in this.regions[r].countries ) {
                        countries.push( this.regions[r].countries[c].country );
                    }
                }

                return countries.join( ' / ' ) + ' ' + this.shape;
            }
        },
        watch: {
            dnaProcessedBy: function(newValue, oldValue) {
                // When dnaProcessedBy changes, reset regions.
                this.resetRegions();
            },
            step: function(newStep, oldStep) {
                if ( newStep == 1 ) {
                    this.$nextTick(function() {
                        DNAanim();
                    });
                } else if ( newStep == 2 ) {
                    console.log('load design images');

                    if ( this.loading ) {
                        return;
                    }

                    var component = this;
                    this.loading = true;

                    // Save data to session after each step change.
                    var dataString = JSON.stringify( {
                        step: this.step,
                        dnaProcessedBy: this.dnaProcessedBy,
                        regions: this.regions,
                        shape: this.shape
                    } );

                    sessionStorage.setItem( 'designState', dataString );
                    $.post( this.appData.ajaxUrl, {
                        action: 'save_design_state',
                        designState: dataString
                    }, function( response ) {
                        // Load design images.
                        var newDesignImages = [];

                        for ( var i in component.appData.shapes ) {
                            newDesignImages.push( {
                                shape: component.appData.shapes[i],
                                url: component.getDesignImageUrl( component.appData.shapes[i] )
                            } );
                        }

                        component.designImages = newDesignImages;
                        component.loading = false;
                    } );
                } else if ( newStep == 3 && oldStep == 2 ) {
                    // if ( this.loading ) {
                    //     return;
                    // }
                    //
                    // var component = this;
                    // this.loading = true;
                    //
                    // var dataString = JSON.stringify( {
                    //     step: this.step,
                    //     regions: this.regions,
                    //     shape: this.shape
                    // } );
                    // sessionStorage.setItem( 'designState', dataString );
                    //
                    // $.post( this.appData.ajaxUrl, {
                    //     action: 'save_design_state',
                    //     data: dataString
                    // }, function( response ) {
                    //     component.loading = false;
                    // } );
                }
            }
        },
        methods: {
            round: function(number, decimalPlaces) {
                return Math.round(number * Math.pow( 10, decimalPlaces )) / Math.pow( 10, decimalPlaces );
            },
            getDesignImageUrl: function(shape) {
                var countryData = [];
                for ( var r in this.regions ) {
                    for ( var c in this.regions[r].countries ) {
                        countryData.push(this.regions[r].countries[c]);
                    }
                }

                var imageData = {
                    shape: shape,
                    countries: countryData
                };

                return this.appData.siteUrl + '/flag-preview/?nonce=' + this.appData.flagPreviewNonce + '&data=' + encodeURI( JSON.stringify( imageData ) );
            },
            addRegion: function(event) {
                this.regions.push({
                    errors: [],
                    region: this.dnaProcessedBy == 'Somewhere else' ? 'All Regions' : '',
                    percent: 0,
                    countries: [
                        {
                            errors: [],
                            country: '',
                            percent: 0
                        }
                    ]
                });
            },
            removeRegion: function(regionIndex) {
                this.regions.splice( regionIndex, 1 );
            },
            addCountry: function(regionIndex) {
                this.regions[regionIndex].countries.push({
                    errors: [],
                    country: '',
                    percent: 0
                });
            },
            removeCountry: function(regionIndex, countryIndex) {
                this.regions[regionIndex].countries.splice( countryIndex, 1 );
            },
            getCountriesInRegion: function( regionName ) {
                if ( this.dnaProcessedBy != '' ) {
                    var regions = this.appData.regions[ this.dnaProcessedBy ];
                    for ( var i in regions ) {
                        var region = regions[i];
                        if ( region.name == regionName ) {
                            return region.countries;
                        }
                    }
                }

                return [];
            },
            isCountrySelectedInRegion: function( country, countryIndex, regionIndex ) {
                for ( var i in this.regions[regionIndex].countries ) {
                    if ( i != countryIndex && this.regions[regionIndex].countries[i].country == country ) {
                        return true;
                    }
                }

                return false;
            },
            setShape: function( shape ) {
                this.shape = shape;
            },
            goToStep: function( step ) {
                // If we're loading something, don't move to the next step yet.
                if ( !this.loading ) {
                    this.step = step;
                }
            },
            resetRegions: function() {
                this.regions.splice( 0, this.regions.length );

                var numRegions = this.dnaProcessedBy == 'Somewhere else' ? 1 : 4;
                for ( var i = 1; i <= numRegions; i++ ) {
                    this.addRegion();
                }

                if ( this.dnaProcessedBy == 'Somewhere else' ) {
                    for ( var i = 1; i <= 3; i++ ) {
                        // Add 3 additional countries to show 4.
                        this.addCountry( 0 );
                    }
                }
            },
            regionPercentChanged: function(regionIndex) {
                // When a region percent changes, distribute countries equally.
                var newPercent = this.regions[regionIndex].percent;
                var numCountries = this.regions[regionIndex].countries.length;
                var newCountryPercent = this.round(newPercent / numCountries, 2);

                _.each( this.regions[regionIndex].countries, function(country) {
                    country.percent = newCountryPercent;
                } );
            },
            validateStep: function( step ) {
                if ( step == 1 ) {
                    var numCountries = 0;
                    var totalPercentage = 0;

                    var hasError = false;

                    // Verify regions and countries.
                    for ( var r in this.regions ) {
                        this.regions[r].errors = [];

                        if ( this.regions[r].region == "" ) {
                            this.regions[r].errors.push( 'Please select a region.' );
                            hasError = true;
                        } else {
                            for ( var c in this.regions[r].countries ) {
                                this.regions[r].countries[c].errors = [];

                                if ( this.regions[r].countries[c].country == "" ) {
                                    this.regions[r].countries[c].errors.push( 'Please select a country.' );
                                    hasError = true;
                                } else {
                                    numCountries++;
                                    totalPercentage += this.regions[r].countries[c].percent;
                                }
                            }
                        }
                    }

                    this.errors = [];

                    if ( totalPercentage != 100 ) {
                        this.errors.push( 'Please make sure your percentages equal 100%.  They currently add up to ' + totalPercentage + '%.' );
                        hasError = true;
                    }

                    if ( !hasError ) {
                        this.step = 2;
                    }
                } else if ( step == 2 ) {
                    this.errors = [];

                    if ( this.shape == "" ) {
                        this.errors.push( 'Please select a design.' );
                    }

                    if ( this.errors.length == 0 ) {
                        // Create a form with the design state and submit it to the designer page so we can reliably save the state on the server.
                        var form = document.createElement( 'form' );
                        form.method = 'post';
                        form.action = '/designer/';

                        var designStateInput = document.createElement('input');
                        designStateInput.type = 'hidden';
                        designStateInput.name = 'designState';
                        designStateInput.value = JSON.stringify( {
                            step: 3,
                            regions: this.regions,
                            shape: this.shape,
                            dnaProcessedBy: this.dnaProcessedBy
                        } );

                        form.appendChild( designStateInput );

                        // Update session.
                        sessionStorage.setItem( 'designState', designStateInput.value );

                        document.body.appendChild( form );
                        form.submit();
                    }
                }
            }
        }
    } );

    document.getElementById('design-app').classList.add('ready');


    window.onorientationchange = function() {  	window.location.reload(); };
    
})( jQuery );
