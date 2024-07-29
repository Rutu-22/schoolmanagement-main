import { faBars,faUser } from '@fortawesome/free-solid-svg-icons';
import { Menu } from '../model/menu';

export const MenuItems: Menu[]=[{
  isActive:false,
  key:'home',
  label:'Home',
  routeUrl:'/home',
  order:1,
  icon: faBars
},{
  isActive:false,
  order:2,
  key:'workstation-status',
  label:'Workstations',
  routeUrl:'/workstation-status/list',
  icon:faUser
}];

