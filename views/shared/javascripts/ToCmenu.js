var barHeight;
$(document).ready(function() {
if ($('#ToCmenu').size() > 0) {
     barHeight = $('#BRtoolbar').height() + $('#BRnav').height();
     $('#ToCmenu').css({
            height : $('#BookReader').height()-barHeight,
            top : barHeight/2
     });
     $(window).resize();
     $('#ToCmenu ul').each(function() {
            if ($(this).parent().attr('id') != 'ToCmenu') {
                   var ul = $(this);
                   var button = $('<a display="block" class="scrollToggle right-arrow" href="">-</a>');
                   ul.hide();
                   ul.prev().before(button);
                   button.css({
                          marginLeft : ($(this).parents('ul').size()-1)*20+4
                   });
                   button.next().hover(function() {
                          button.addClass('hover');
                   },function() {
                          button.removeClass('hover');
                   });
                   button.click(function() {
                          if (button.hasClass('right-arrow')) {
                                 button.removeClass('right-arrow').addClass('down-arrow');
                          } else {
                                 button.removeClass('down-arrow').addClass('right-arrow');
                          }
                          ul.slideToggle(400,function() {
                                 $('#ToCbutton').css({
                                        left : $('#ToCmenu').width(),
                                        top : $('#ToCmenu').position()['top']
                                 });
                          });
                          $('#ToCbutton').css({
                                 left : $('#ToCmenu').width(),
                                 top : $('#ToCmenu').position()['top']
                          });
                          return false;
                   });
            }
     });
     $('#ToCmenu li').each(function() {
            $(this).css({
                   paddingLeft : $(this).parents('ul').size()*20,
                   fontSize : (16-$(this).parents('ul').size()*2)+'px'
            });
     });
     $('#ToCbutton').css({
            left : $('#ToCmenu').width(),
            top : $('#ToCmenu').position()['top']
     });
     $(window).resize(function() {
            if ($('#BRnavCntlBtm').hasClass('BRup')) {
                   $('#ToCmenu').css({
                          height : $('#BookReader').height(),
                          top : 0
                   });
            } else {
                   $('#ToCmenu').css({
                          height : $('#BookReader').height()-barHeight,
                          top : barHeight/2
                   });
            }
            $('#ToCbutton').css({
                   left : $('#ToCmenu').position()['left']+$('#ToCmenu').width(),
                   top : $('#ToCmenu').position()['top']
            });
     });
     $('#BRnavCntlBtm').click(function() {
          if ($(this).hasClass('BRup')) {
                 $('#ToCmenu').animate({
                        height : $('#BookReader').height(),
                        top : 0
                 },{
                        step : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                               $('#ToCbutton').css({
                                      top : $('#ToCmenu').position()['top']
                               });
                        },
                        complete : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                        }
                 });
          } else {
                 $('#ToCmenu').animate({
                        height : $('#BookReader').height()-barHeight,
                        top : barHeight/2
                 },{
                        step : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                               $('#ToCbutton').css({
                                      top : $('#ToCmenu').position()['top']
                               });
                        },
                        complete : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                        }
                 });
          }

     });
     $('#ToCbutton').click(function() {
          if ($(this).hasClass('open')) {
                 $(this).removeClass('open').addClass('close');
                 $(this).animate({
                        left : 0
                 });
                 $('#ToCmenu').animate({
                        left : -$('#ToCmenu').width()
                 },{
                        step : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                        },
                        complete : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                        },
                        queue : false
                 });
          } else {
                 $(this).removeClass('close').addClass('open');
                 $(this).animate({
                        left : $('#ToCmenu').width()
                 })
                 $('#ToCmenu').animate({
                        left : 0
                 },{
                        step : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                        },
                        complete : function() {
                               $('#ToCmenu').css('overflow-y','auto');
                        },
                        queue : false
                 });
                 $('#ToCmenu').animate({opacity:1})
          }
     });
   $('#ToCbutton').mouseover(function(){
        if ($(this).hasClass('close')) {
            $('#ToCbutton').animate({opacity:1},{
               duration : 500,
               queue : false
            });
        };
    });
    $('#ToCbutton').mouseleave(function(){
        if ($(this).hasClass('close')) {
            $('#ToCbutton').animate({opacity:.25},{
               duration : 500,
               queue : false
            });
        };
    });
   }
});
