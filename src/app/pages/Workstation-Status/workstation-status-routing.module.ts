import { WorkstationStatusComponent } from './containers/workstation-status.component';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

const routes: Routes = [
{
  path: 'list',
  component: WorkstationStatusComponent,
  resolve:{

  },
},];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class WorkStationStatusRoutingModule { }
