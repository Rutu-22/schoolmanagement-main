import { Component, OnInit } from '@angular/core';
import { faUser } from '@fortawesome/free-solid-svg-icons';

@Component({
  selector: 'app-top-bar',
  templateUrl: './top-bar.component.html',
  styleUrls: ['./top-bar.component.scss'],
})
export class TopBarComponent implements OnInit {
  faUser = faUser;
  userName: string = '';
  userRole: string = '';
  userSettingKey: string = 'Role';
  constructor( ) {}

  ngOnInit() {
    this.getAdRolebyUserName();
  }
  ngOnDestroy(): void {}
  getAdRolebyUserName() {

  }
}
