$('document').ready(function(){

        var value = 0
                $(".bigshotContainer").rotate({
                bind:
                {
                    click: function(){
                    value +=90;
                    $('.bigshotContainer').rotate({ animateTo:value})
                    }
                }
                });



});
       