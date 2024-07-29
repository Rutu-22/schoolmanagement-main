import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { WorkStationStatusRoutingModule } from './workstation-status-routing.module';
import { WorkstationStatusComponent } from './containers/workstation-status.component';

@NgModule({
  declarations: [
    WorkstationStatusComponent
  ],
  imports: [
    CommonModule,
    WorkStationStatusRoutingModule,
  ],
  providers: [],
  bootstrap: [WorkstationStatusComponent]
})
export class WorkStationStatusModule { }
