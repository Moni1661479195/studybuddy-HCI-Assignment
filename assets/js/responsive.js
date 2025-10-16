// simple mobile nav toggle and safety for back-button
document.addEventListener('DOMContentLoaded', function(){
  var btn = document.getElementById('nav-toggle');
  if(!btn) return;
  btn.addEventListener('click', function(){
    document.body.classList.toggle('nav-open');
  });

  // Close mobile nav on outside click
  document.addEventListener('click', function(e){
    var nav = document.querySelector('.nav-links');
    if(!nav) return;
    var isClickInside = nav.contains(e.target) || btn.contains(e.target);
    if(!isClickInside){
      document.body.classList.remove('nav-open');
    }
  });

  // Close nav when viewport becomes larger
  window.addEventListener('resize', function(){
    if(window.innerWidth >= 640){
      document.body.classList.remove('nav-open');
    }
  });

  // ensure Back from protected page doesn't leave mobile menu open
  window.addEventListener('pageshow', function(ev){
    if(ev.persisted) document.body.classList.remove('nav-open');
  });
});

