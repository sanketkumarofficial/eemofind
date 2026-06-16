import './bootstrap';
import * as bootstrap from 'bootstrap';
import ApexCharts from 'apexcharts';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import Swal from 'sweetalert2';
import toastr from 'toastr';
window.bootstrap=bootstrap;window.ApexCharts=ApexCharts;window.Swal=Swal;window.toastr=toastr;
document.addEventListener('DOMContentLoaded',()=>{const saved=localStorage.getItem('eemo-theme')||document.documentElement.dataset.defaultTheme||'light';document.documentElement.setAttribute('data-bs-theme',saved);document.querySelector('[data-theme-toggle]')?.addEventListener('click',()=>{const next=document.documentElement.getAttribute('data-bs-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-bs-theme',next);localStorage.setItem('eemo-theme',next)});document.querySelector('[data-sidebar-toggle]')?.addEventListener('click',()=>document.body.classList.toggle('sidebar-open'));document.querySelectorAll('.data-table').forEach(table=>new DataTable(table,{responsive:true,pageLength:20,searching:false,lengthChange:false}));document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',async event=>{event.preventDefault();const result=await Swal.fire({title:'Are you sure?',text:form.dataset.confirm,icon:'warning',showCancelButton:true,confirmButtonColor:'#0f9f91'});if(result.isConfirmed)form.submit()}));if(window.flashSuccess)toastr.success(window.flashSuccess);if(window.flashError)toastr.error(window.flashError)});
