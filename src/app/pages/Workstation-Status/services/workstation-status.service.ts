import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from 'src/environments/environment';
import { hubServices } from '../../../common/constant/workstation-hub-api-url';

@Injectable({
  providedIn: 'root',
})
export class WorkstationStatusService {
  constructor(private http: HttpClient) {}


}
