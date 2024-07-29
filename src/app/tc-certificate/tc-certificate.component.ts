import { Component, Input } from '@angular/core';
import { ActivatedRoute, Params } from '@angular/router';

@Component({
  selector: 'app-tc-certificate',
  templateUrl: './tc-certificate.component.html',
  styleUrls: ['./tc-certificate.component.scss']
})
export class TcCertificateComponent {
  student: any;
  @Input() studentId: string = '';
  @Input() uidNumber: string = '';
  @Input() studentFullName: string = '';
  @Input() motherName: string = '';
  @Input() region: string = '';
  @Input() casteAndSubcaste: string = '';
  @Input() placeOfBirth: string = '';
  @Input() birthDate: string = '';
  @Input() schoolAttendedBefore: string = '';
  @Input() studyProgress: string = '';
  @Input() behavior: string = '';
  @Input() dateOfLeavingSchool: string = '';
  @Input() gradeAtLeaving: string = '';
  @Input() reasonForDroppingOut: string = '';
  @Input() grade: string = '';
   
  constructor(private route: ActivatedRoute) { }
     ngOnInit(): void {
      this.route.queryParams.subscribe((params: Params) => {
       this.student = params;
    });
}
}