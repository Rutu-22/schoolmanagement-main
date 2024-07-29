import { Component } from '@angular/core';

@Component({
  selector: 'dashboard-layout',
  templateUrl: './dashboard-layout.component.html',
  styleUrls: ['./dashboard-layout.component.scss']
})
export class DashboardLayoutComponent {
  sidebarExpanded = true;
  title = 'Aspire Evolution UI';
}
