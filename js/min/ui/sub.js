(function(){var a=window.KON=window.KON||{},e=a.UI=a.UI||{},b=e.Sub=e.Sub||{};a.modules=a.modules||{};a.modules["UI.Sub"]=!0;b.__init__=function(m){delete b.__init__;a.load("UI.Tmpl",function(){var f=a.$,l=e.Tmpl;b=e.Sub=function(a){if(!(this instanceof b))return new b(a);a=a||{};l.prototype.constructor.call(this,a);this.playing=!0;this.since=this.sub=null;this.seen={};this.$articles=[];this.$paused=[];this.queue=a.queue||[];return this};b.prototype=Object.create(l.prototype);b.prototype.render=function(){l.prototype.render.call(this);
a.$body.addClass("kon-sub");this.sub=a.$body.data("sub")||"all";this.$play=f('<button class="kon-pause"><hr><hr></button>');this.$play.click(function(){(this.playing=!this.playing)?this.play():this.pause()}.bind(this));a.$header.children(".kon-wrapper").prepend(this.$play);var k=this.queue.length;if(k){this.since=this.queue[0].id;for(var c=0;c<k;c++)this.seen[this.queue[c].id]=!0}this.$waiting=f('<p style="display: none">Waiting for more posts...</p>');a.$messages.append(this.$waiting);this.renderPost(!0);
this.renderPost();setInterval(this.renderPost.bind(this),5E3);this.fetchPosts();setInterval(this.fetchPosts.bind(this),6E4);setInterval(this.updateTS.bind(this),15E3);return this};b.prototype.renderPost=function(k){this.queue.length?this.$waiting.hide():this.$waiting.fadeIn(300);if(this.playing){var c=this.queue.pop();if(c){var b=f('<article style="display: none" data-id="'+c.id+'" data-ts="'+c.created+'"><span class="kon-ts">'+a.ago(c.created)+'</span><h2><a href="'+c.url+'">'+c.title+'</a></h2><p>posted by <a href="'+
a.urls.reddit+"u/"+c.author+'">'+c.author+'</a> in <a href="'+a.urls.www+"r/"+c.sub+'">/r/'+c.sub+'</a></p><p class="kon-image"><a href="'+c.url+'"><img src="'+c.url+'"></a></p><p><a href="'+a.urls.reddit+"r/"+c.sub+"/comments/"+c.id+'">comments</a></p></article>'),d=f('<span class="kon-votes" data-vote="none">'),g=f('<button class="kon-upvote">'),h=f('<button class="kon-downvote">');d.append(g);d.append(h);b.prepend(d);g.click(function(b){if(a.loggedIn)return"up"===d.data("vote")?(d.data("vote",
"none"),g.removeClass("kon-voted"),a.request("post/vote",{sid:a.sid,id:c.id,dir:0})):(d.data("vote","up"),g.addClass("kon-voted"),a.request("post/vote",{sid:a.sid,id:c.id,dir:1})),h.removeClass("kon-voted"),b.preventDefault&&b.preventDefault(),!1;a.login()});h.click(function(b){if(a.loggedIn)return"down"===d.data("vote")?(d.data("vote","none"),h.removeClass("kon-voted"),a.request("post/vote",{sid:a.sid,id:c.id,dir:0})):(d.data("vote","down"),h.addClass("kon-voted"),a.request("post/vote",{sid:a.sid,
id:c.id,dir:-1})),g.removeClass("kon-voted"),b.preventDefault&&b.preventDefault(),!1;a.login()});a.$main.prepend(b);setTimeout(function(){this.playing?b.fadeIn(300):this.$paused.push(b)}.bind(this),k?1:5E3);this.$articles.unshift(b);if(10<this.$articles.length){var e=this.$articles.pop();e.fadeOut(300,function(){e.remove()})}}}};b.prototype.play=function(){this.$play.removeClass("kon-play");this.$play.addClass("kon-pause");for(var a=0,c=this.$paused.length;a<c;a++)this.$paused[a].fadeIn(300);this.$paused=
[]};b.prototype.pause=function(){this.$play.removeClass("kon-pause");this.$play.addClass("kon-play")};b.prototype.updateTS=function(){for(var b=0,c=this.$articles.length;b<c;b++){var e=this.$articles[b];e.children(".kon-ts").text(a.ago(e.data("ts")))}};b.prototype.fetchPosts=function(){var b={sub:this.sub};this.since&&(b.since=this.since);a.request("post/list",b,function(a){if(200===a.status.code){a=a.result.posts;for(var b=a.length-1;-1<b;b--){var d=a[b];this.seen[d.id]||(this.seen[d.id]=!0,this.since=
d.id,this.queue.unshift(d),100<this.queue.length&&(this.queue=this.queue.slice(0,99)))}}}.bind(this))};m()})}})();