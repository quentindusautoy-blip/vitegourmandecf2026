'use strict';
const filterNodes = ['filter-theme','filter-diet','filter-max','filter-min','filter-people'].map(id => document.getElementById(id));
if (filterNodes[0]) {
  const cards = [...document.querySelectorAll('.filter-card')];
  const output = document.getElementById('filter-count');
  function updateFilters() {
    const [theme, diet, max, min, people] = filterNodes.map(el => el.value.trim());
    let shown = 0;
    cards.forEach(card => {
      const price=Number(card.dataset.price), minimumPeople=Number(card.dataset.people);
      const visible=(!theme||card.dataset.theme===theme) && (!diet||card.dataset.diet===diet)
        && (!max||price<=Number(max)) && (!min||price>=Number(min))
        && (!people||minimumPeople<=Number(people));
      card.hidden=!visible;
      if(visible) shown++;
    });
    output.textContent=`${shown} menu${shown===1?'':'s'} trouvé${shown===1?'':'s'}`;
  }
  filterNodes.forEach(node => node.addEventListener('input',updateFilters));
  updateFilters();
}
const people=document.getElementById('people');
if (people) {
  const city=document.getElementById('delivery-city'), distance=document.getElementById('distance-km');
  const output=document.getElementById('price-preview');
  const money=n => (n/100).toLocaleString('fr-FR',{style:'currency',currency:'EUR'});
  function preview() {
    const p=Number(people.value), min=Number(people.dataset.minPeople), base=Number(people.dataset.basePrice);
    if(p<min||p>500||!Number.isInteger(p)){output.textContent=`Nombre minimum : ${min} personnes.`;return;}
    const subtotal=Math.round(base*p/min), discount=p>=min+5?Math.round(subtotal*0.1):0;
    const outside=city.value.trim().toLocaleLowerCase('fr-FR')!=='bordeaux';
    const km=Number(distance.value); const shipping=outside&&km>0 ? 500+Math.round(km*59):0;
    const missing=outside&&!(km>0);
    output.textContent=`Menu : ${money(subtotal)} · remise : −${money(discount)} · livraison : ${missing?'à estimer':money(shipping)} · total : ${missing?'à calculer':money(subtotal-discount+shipping)}`;
  }
  [people,city,distance].forEach(el=>el.addEventListener('input',preview)); preview();
}
