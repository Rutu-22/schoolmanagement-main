import { WorkstationSatus } from './workstation-satus.model';
export interface Workstation {
  id: string;
  fullDeviceName: string;
  loginStatus: WorkstationSatus;
}
