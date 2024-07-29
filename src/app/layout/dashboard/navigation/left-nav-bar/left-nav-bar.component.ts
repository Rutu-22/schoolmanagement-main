import { Component, Input, Output, EventEmitter } from '@angular/core';
import { faBars,faUser } from '@fortawesome/free-solid-svg-icons';
import { Menu } from 'src/app/common/model/menu';

@Component({
  selector: 'app-left-nav-bar',
  templateUrl: './left-nav-bar.component.html',
  styleUrls: ['./left-nav-bar.component.scss']
})
export class LeftNavBarComponent {
  @Input() isExpanded: boolean = false;
  public collapsed = false;
  faUser=faUser;
   faBars=faBars;
  @Output() toggleSidebar: EventEmitter<boolean> = new EventEmitter<boolean>();
  handleSidebarToggle = () => this.toggleSidebar.emit(!this.isExpanded);
  constructor(){

  }

  ngOnInit(): void {
  }
}
